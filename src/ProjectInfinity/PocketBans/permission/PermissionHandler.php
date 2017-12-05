<?php

namespace ProjectInfinity\PocketBans\permission;

use pocketmine\command\CommandSender;

class PermissionHandler {

    public static function canBan(CommandSender $sender, $type = 'local'): bool {
        switch(strtoupper($type)) {
            case 'LOCAL':
                return $sender->hasPermission(Permissions::BAN_LOCAL);
            case 'GLOBAL':
                return $sender->hasPermission(Permissions::BAN_GLOBAL);
            case 'TEMP':
                return $sender->hasPermission(Permissions::BAN_TEMP);
            default:
                return false;
        }
    }

    public static function canKick(CommandSender $sender): bool {
        return $sender->hasPermission(Permissions::KICK);
    }

    public static function canKickAll(CommandSender $sender): bool {
        return $sender->hasPermission(Permissions::KICK_ALL);
    }
}