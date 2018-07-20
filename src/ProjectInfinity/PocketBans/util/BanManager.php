<?php

namespace ProjectInfinity\PocketBans\util;

use ProjectInfinity\PocketBans\data\Ban;
use ProjectInfinity\PocketBans\data\BanType;
use ProjectInfinity\PocketBans\database\DataProvider;
use ProjectInfinity\PocketBans\database\FlatfileDataProvider;
use ProjectInfinity\PocketBans\PocketBans;

class BanManager {

    private $plugin;
    private $provider;
    private $bans;

    public function __construct(PocketBans $plugin) {
        $this->plugin = $plugin;

        switch(strtolower($plugin->getConfig()->get('provider'))) {

            case 'flat':
            case 'file':
            case 'json':
            case 'flatfile':
                $this->provider = new FlatfileDataProvider();
                break;

            default:
                $plugin->getLogger()->warning("The specified provider '{$plugin->getConfig()->get('provider')}' is invalid, defaulting to flatfile.");
                $this->provider = new FlatfileDataProvider();
        }
        $this->bans = $this->provider->getBans();
    }

    public function shutdown(): void {
        $this->provider->close();
    }

    public function getProvider(): DataProvider {
        return $this->provider;
    }

    public function cacheXuid(string $player, string $xuid): void {
        $this->provider->storeXuid($player, $xuid);
    }

    /**
     * TODO feature list for BanManager.
     * - Log bans, unbans, kicks etc.
     */

    /**
     * Checks if the player is banned. By default includes checking for temporary bans.
     * If $checkDatabase is set to true we will schedule a async thread to check for
     * bans in the database.
     *
     * Returns a integer referencing the type of ban or 0 if not banned.
     *
     * @param string $xuid
     * @param bool $includeTemp
     * @param bool $checkDatabase
     * @return int
     */
    public function isBanned(string $xuid, $includeTemp = true, $checkDatabase = false): int {
        if(isset($this->bans[BanType::LOCAL][$xuid])) return BanType::LOCAL;
        if(isset($this->bans[BanType::GLOBAL][$xuid])) return BanType::GLOBAL;
        if($includeTemp && isset($this->bans[BanType::TEMP][$xuid])) return BanType::TEMP;
        # TODO: Do something with $checkDatabase.
        return 0;
    }

    /**
     * Attempts to get the ban details of the specified player.
     * Specifying a ban type will improve performance.
     *
     * Note that when using SQLite this will always query the database
     * since we want to avoid multiple concurrent queries to it.
     *
     * @param string $player
     * @param int|null $type
     * @return Ban|null
     */
    public function getBan(string $player, ?int $type = null): ?Ban {
        if($type !== null) {
            $ban = $this->bans[$type][$player] ?? null;
        } else {
            $ban = $this->bans[BanType::GLOBAL][$player] ?? $this->bans[BanType::LOCAL] ?? $this->bans[BanType::TEMP] ?? null;
        }
        # TODO: IF null, check database.
        //$ban = null;

        # Check database too if using SQLite if a ban wasn't found in cache.
        if($ban === null && $this->provider instanceof SqliteDataProvider) {
            $ban = $this->provider->getBan($player);
        }

        return $ban;
    }

    public function banPlayer(string $player, string $reason, string $xuid, int $type, string $sender, $expires = null): bool {
        if($this->isBanned($player, false)) return false;

        $created = time();

        $ban = new Ban(
            $player,
            $type,
            $sender,
            $reason,
            $xuid,
            $expires,
            $created
        );

        $result = $this->provider->ban($ban);

        if($result) {
            $this->bans[$type][$player] = new Ban(
                $player,
                $type,
                $sender,
                $reason,
                $xuid,
                $expires,
                $created
            );
        }

        return $result;
    }

}