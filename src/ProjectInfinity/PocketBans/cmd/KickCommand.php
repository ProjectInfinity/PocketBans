<?php

namespace ProjectInfinity\PocketBans\cmd;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use ProjectInfinity\PocketBans\action\KickAction;
use ProjectInfinity\PocketBans\lang\Message;
use ProjectInfinity\PocketBans\permission\PermissionHandler;
use ProjectInfinity\PocketBans\PocketBans;

class KickCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(PocketBans $plugin) {
        parent::__construct('kick', 'Kicks a player from the server', '/kick <player> [reason]');
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!PermissionHandler::canKick($sender)) {
            Message::send($sender, 'no_permission');
            return true;
        }
        if(\count($args) < 1 || $args[0] === '') {
            Message::send($sender, 'not_enough_args', ['/kick <PLAYER> [REASON]']);
            return true;
        }

        $target = $args[0];

        if($target === '*' && !PermissionHandler::canKickAll($sender)) {
            Message::send($sender, 'no_permission');
            return true;
        }

        $reason = null;

        if(\count($args) > 1) {
            unset($args[0]);
            $reason = implode(' ', $args);
        }

        new KickAction($target, $reason, $sender);

        return true;
    }

    public function getPlugin(): Plugin {
        return $this->plugin;
    }

}