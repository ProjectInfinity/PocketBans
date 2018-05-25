<?php

namespace ProjectInfinity\PocketBans\cmd;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use ProjectInfinity\PocketBans\action\BanAction;
use ProjectInfinity\PocketBans\data\BanType;
use ProjectInfinity\PocketBans\lang\Message;
use ProjectInfinity\PocketBans\permission\PermissionHandler;
use ProjectInfinity\PocketBans\PocketBans;

class BanCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;
    private $bm;

    public function __construct(PocketBans $plugin) {
        parent::__construct('ban', 'Bans a player from the server', '/ban <player> [reason]');
        $this->plugin = $plugin;
        $this->bm = $plugin->getBanManager();
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!PermissionHandler::canBan($sender)) {
            Message::send($sender, 'no_permission');
            return true;
        }
        if(\count($args) < 1 || $args[0] === '') {
            Message::send($sender, 'not_enough_args', ['/ban <PLAYER> [REASON]']);
            return true;
        }

        $target = $args[0];
        $force = false;
        $reason = null;

        if(\count($args) > 1) {
            if($args[\count($args) - 1] === '--force') $force = true;
            if($force) {
                unset($args[\count($args) - 1], $args[0]);
                \count($args) > 0 ? $reason = implode(' ', $args) : null;
            } else {
                unset($args[0]);
                $reason = implode(' ', $args);
            }
        }

        $targetPlayer = $this->getPlugin()->getServer()->getOfflinePlayer($target);
        if(!$force && !$targetPlayer->hasPlayedBefore()) {
            Message::send($sender, 'player_not_found', [$target]);
            return true;
        }

        $userStatus = $this->bm->isBanned($targetPlayer === null ? $target : $targetPlayer->getName());
        if($userStatus > BanType::TEMP) {
            Message::send($sender, 'ban.already_banned', [$target]);
            return true;
        }

        new BanAction($target, $reason, BanType::LOCAL, $sender);

        return true;
    }

    public function getPlugin(): Plugin {
        return $this->plugin;
    }

}