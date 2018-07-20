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
        $reason = null;

        if(\count($args) > 1) {
            unset($args[0]);
            $reason = implode(' ', $args);
        }

        $targetPlayer = $this->getPlugin()->getServer()->getOfflinePlayer($target);
        if(!$targetPlayer->hasPlayedBefore()) {
            Message::send($sender, 'player_not_found', [$target]);
            return true;
        }

        if($targetPlayer->getXuid() === '' && !$this->bm->getProvider()->hasXuidCache($target)) {
            Message::send($sender, 'ban.failure', $target);
            return true;
        }

        $xuid = $targetPlayer->getXuid() !== '' ? $targetPlayer->getXuid() : $this->bm->getProvider()->getXuidCache($targetPlayer->getName());

        $userStatus = $this->bm->isBanned($xuid);
        if($userStatus > BanType::TEMP) {
            Message::send($sender, 'ban.already_banned', [$targetPlayer->getName()]);
            return true;
        }

        new BanAction($targetPlayer, $xuid, $reason, BanType::LOCAL, $sender);

        return true;
    }

    public function getPlugin(): Plugin {
        return $this->plugin;
    }

}