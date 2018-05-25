<?php

namespace ProjectInfinity\PocketBans\util;

use ProjectInfinity\PocketBans\data\Ban;
use ProjectInfinity\PocketBans\data\BanType;
use ProjectInfinity\PocketBans\database\FlatfileDataProvider;
use ProjectInfinity\PocketBans\database\MysqlDataProvider;
use ProjectInfinity\PocketBans\database\SqliteDataProvider;
use ProjectInfinity\PocketBans\PocketBans;

class BanManager {

    private $plugin;
    private $db;
    private $bans;

    public function __construct(PocketBans $plugin) {
        $this->plugin = $plugin;

        switch(strtolower($plugin->getConfig()->get('provider'))) {

            case 'flat':
            case 'file':
            case 'json':
            case 'flatfile':
                $this->db = new FlatfileDataProvider();
                break;

            default:
                $plugin->getLogger()->warning("The specified provider '{$plugin->getConfig()->get('provider')}' is invalid, defaulting to flatfile.");
                $this->db = new FlatfileDataProvider();
        }
        $this->db = $plugin->getConfig()->getNested('mysql.enabled', false) ? new MysqlDataProvider() : new SqliteDataProvider();
        $this->bans = $this->db->getBans();
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
     * @param string $player
     * @param bool $includeTemp
     * @param bool $checkDatabase
     * @return int
     */
    public function isBanned(string $player, $includeTemp = true, $checkDatabase = false): int {
        if(isset($this->bans[BanType::LOCAL][$player])) return BanType::LOCAL;
        if(isset($this->bans[BanType::GLOBAL][$player])) return BanType::GLOBAL;
        if($includeTemp && isset($this->bans[BanType::TEMP][$player])) return BanType::TEMP;
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
        $ban = null;

        # Check database too if using SQLite if a ban wasn't found in cache.
        if($ban === null && $this->db instanceof SqliteDataProvider) {
            $ban = $this->db->getBan($player);
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
            $expires,
            $created
        );

        $result = $this->db->ban($ban);

        if($result) {
            $this->bans[$type][$player] = new Ban(
                $player,
                $type,
                $sender,
                $reason,
                $expires,
                $created
            );
        }

        return $result;
    }

}