<?php

namespace ProjectInfinity\PocketBans\database;

use ProjectInfinity\PocketBans\data\Ban;
use ProjectInfinity\PocketBans\data\BanType;
use ProjectInfinity\PocketBans\PocketBans;

class FlatfileDataProvider extends DataProvider {

    private $plugin;
    private $bans = [BanType::TEMP => [], BanType::GLOBAL => [], BanType::LOCAL => []];
    private $history = [];

    public function __construct() {
        $this->plugin = PocketBans::getPlugin();
        $this->plugin->getLogger()->info('Using flatfile provider.');
        $decoded = json_decode($this->loadFile(), true);
        if($decoded === null) {
            $this->plugin->getLogger()->error('Failed to decode bans.json! File is being moved to bans.json.bak and a new file is being created.');
            rename($this->plugin->getDataFolder().'/bans.json', $this->plugin->getDataFolder().'/bans.json.bak');
            $this->createFile();
        }

        foreach($decoded['bans']['perm'] as $perm) {
            //var_dump($perm);
            $this->bans[$perm['type']][] = $perm;
        }

        foreach($decoded['bans']['temp'] as $temp) {
            //var_dump($temp);
            $this->bans[$temp['type']][] = $temp;
        }

        //var_dump($this->bans);
        // TODO: Add history support.
    }

    private function loadFile(): ?String {
        if(!file_exists($this->plugin->getDataFolder().'/bans.json')) {
            $this->plugin->getLogger()->info('Couldn\'t find bans.json, creating it.');
            $this->createFile();
        }
        $handle = fopen($this->plugin->getDataFolder().'/bans.json', 'rb');

        if($handle) {
            $content = '';
            while(($line = fgets($handle)) !== false) {
                $content .= $line;
            }
            fclose($handle);
            return $content;
        }

        $this->plugin->getLogger()->error('Failed to open bans.json!');
        return null;
    }

    private function createFile(): void {
        file_put_contents($this->plugin->getDataFolder().'/bans.json', json_encode([
            'history' => [],
            'bans' => [
                'perm' => [], // Do not differentiate between local and global here, that will be a property on the ban instead.
                'temp' => []
            ]
        ]));
    }

    public function close(): void {
        $this->save();
        $this->plugin->getLogger()->info('Saved bans to disk.');
    }

    private function save(): void {
        file_put_contents($this->plugin->getDataFolder().'/bans.json', json_encode([
            'history' => $this->history,
            'bans' => [
                'perm' => array_merge($this->bans[BanType::GLOBAL], $this->bans[BanType::LOCAL]),
                'temp' => $this->bans[BanType::TEMP]
            ]
        ], PocketBans::$dev ? JSON_PRETTY_PRINT : 0));
    }

    public function getBans(): array {
        return [
            BanType::TEMP => $this->bans[BanType::TEMP],
            BanType::LOCAL => $this->bans[BanType::LOCAL],
            BanType::GLOBAL => $this->bans[BanType::GLOBAL]
        ];
    }

    public function getBan(string $player): ?Ban {
        // TODO: Implement getBan() method.
    }

    /**
     * Bans the user and returns whether the ban array is now bigger than before.
     * If $save is set to true, changes will be instantly saved to disk.
     *
     * @param Ban $ban
     * @param bool $save
     * @return bool
     */
    public function ban(Ban $ban, $save = true): bool {
        $before = \count($this->bans[$ban->getType()]);
        $this->bans[$ban->getType()][] = $ban->toArray();
        if($save) $this->save();
        // TODO: Store history.
        return \count($this->bans[$ban->getType()]) > $before;
    }
}