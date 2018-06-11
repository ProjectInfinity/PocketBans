<?php

namespace ProjectInfinity\PocketBans\database;

use ProjectInfinity\PocketBans\data\Ban;
use ProjectInfinity\PocketBans\data\BanType;
use ProjectInfinity\PocketBans\PocketBans;
use ProjectInfinity\PocketBans\util\ToolBox;

class FlatfileDataProvider extends DataProvider {

    private $plugin;
    private $bans = [BanType::TEMP => [], BanType::GLOBAL => [], BanType::LOCAL => []];
    private $history = [];
    private $xuidCache = [];

    public function __construct() {
        $this->plugin = PocketBans::getPlugin();
        $this->plugin->getLogger()->info('Using flatfile provider.');

        $decoded = json_decode($this->loadBansFile(), true);
        if($decoded === null) {
            $this->plugin->getLogger()->error('Failed to decode bans.json! File is being moved to bans.json.bak and a new file is being created.');
            rename($this->plugin->getDataFolder().'/bans.json', $this->plugin->getDataFolder().'/bans.json.bak');
            $this->createBansFile();
        }

        foreach($decoded['bans']['perm'] as $perm) {
            //var_dump($perm);
            $this->bans[$perm['type']][] = $perm;
        }

        foreach($decoded['bans']['temp'] as $temp) {
            //var_dump($temp);
            $this->bans[$temp['type']][] = $temp;
        }

        $decoded = json_decode($this->loadXuidCache(), true);

        if($decoded === null) {
            $this->plugin->getLogger()->error('Failed to decode xuid.json! File is being moved to xuid.json.bak and a new file is being created.');
            rename($this->plugin->getDataFolder().'/xuid.json', $this->plugin->getDataFolder().'/xuid.json.bak');
            $this->createXuidCache();
        }

        foreach($decoded as $key => $value) {
            $this->xuidCache[$value['name']] = (object) ['xuid' => $value['xuid'], 'score' => $value['score'], 'name' => $value['name']];
        }

        $this->xuidCleanup();

        //var_dump($this->bans);
        // TODO: Add history support.
    }

    private function loadBansFile(): ?String {
        if(!file_exists($this->plugin->getDataFolder().'/bans.json')) {
            $this->plugin->getLogger()->info('Couldn\'t find bans.json, creating it.');
            $this->createBansFile();
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

    private function createBansFile(): void {
        file_put_contents($this->plugin->getDataFolder().'/bans.json', json_encode([
            'history' => [],
            'bans' => [
                'perm' => [], // Do not differentiate between local and global here, that will be a property on the ban instead.
                'temp' => []
            ]
        ]));
    }

    private function loadXuidCache(): ?String {
        if(!file_exists($this->plugin->getDataFolder().'/xuid.json')) {
            $this->plugin->getLogger()->info('Couldn\'t find xuid.json, creating it.');
            $this->createXuidCache();
        }
        $handle = fopen($this->plugin->getDataFolder().'/xuid.json', 'rb');

        if($handle) {
            $content = '';
            while(($line = fgets($handle)) !== false) {
                $content .= $line;
            }
            fclose($handle);
            return $content;
        }

        $this->plugin->getLogger()->error('Failed to open xuid.json!');
        return null;
    }

    private function createXuidCache(): void {
        file_put_contents($this->plugin->getDataFolder().'/xuid.json', json_encode([]));
    }

    private function xuidCleanup(): void {
        if(\count($this->xuidCache) <= $this->plugin->getConfig()->getNested('cache.xuid.max', 5000)) return;
        $temp = ToolBox::xuidQuickSort(array_values($this->xuidCache));
        $this->xuidCache = array_splice($temp, 0, $this->plugin->getConfig()->getNested('cache.xuid.max', 5000));
        }

    public function close(): void {
        $this->save(true);
        $this->plugin->getLogger()->info('Saved bans and xuid cache to disk.');
    }

    private function save($isShutdown = false): void {
        file_put_contents($this->plugin->getDataFolder().'/bans.json', json_encode([
            'history' => $this->history,
            'bans' => [
                'perm' => array_merge($this->bans[BanType::GLOBAL], $this->bans[BanType::LOCAL]),
                'temp' => $this->bans[BanType::TEMP]
            ]
        ], PocketBans::$dev ? JSON_PRETTY_PRINT : 0));

        if($isShutdown) file_put_contents($this->plugin->getDataFolder().'/xuid.json', json_encode(array_values($this->xuidCache), PocketBans::$dev ? JSON_PRETTY_PRINT : 0));
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

    public function storeXuid(string $player, string $xuid): void {
        if(!isset($this->xuidCache[$player])) $this->xuidCache[$player] = ['xuid' => $xuid, 'score' => 0];
        $new = $this->xuidCache[$player];
        $new['score']++;
        $new['xuid'] = $xuid;
        $this->xuidCache[$player] = $new;
        $this->plugin->getLogger()->debug('Cached player ('.$player.', '.$xuid.', '.$new['score'].')');
    }
}