<?php

namespace ProjectInfinity\PocketBans\action;

use pocketmine\command\CommandSender;
use ProjectInfinity\PocketBans\lang\Message;
use ProjectInfinity\PocketBans\permission\PermissionHandler;
use ProjectInfinity\PocketBans\PocketBans;

class BanAction {

    private $plugin;
    private $target;
    private $reason;
    private $type;
    private $sender;
    private $manager;

    public function __construct(string $target, ?string $reason, int $type, CommandSender $sender) {
        $this->plugin = PocketBans::getPlugin();
        $this->manager = $this->plugin->getBanManager();

        $this->target = $target;
        $this->reason = $reason ?? Message::get('no_reason');
        $this->type = $type;
        $this->sender = $sender;

        $this->run();
    }

    private function run(): void {
        $targetPlayer = $this->plugin->getServer()->getPlayer($this->target);
        #$this->plugin->getServer()->getNameBans()->addBan();

        # Kick the player if online and show a ban message.
        if($targetPlayer !== null) {
            $this->target = $targetPlayer->getName();
            # Check if the player is exempted before continuing.
            if(PermissionHandler::isExempted($targetPlayer, true)) {
                Message::send($this->sender, 'exempt.ban', [$this->target]);
                return;
            }
            $targetPlayer->kick(Message::get('ban.message', [$this->reason]), false);
        }

        if(!$this->manager->banPlayer($this->target, $this->reason, $targetPlayer->getXuid(), $this->type, $this->sender->getName())) {
            Message::send($this->sender, 'ban.failure', [$this->target]);
            return;
        }

        # TODO: Figure out if we have to send our own ban event here.
        $this->plugin->getServer()->broadcastMessage(Message::get('ban.broadcast', [$this->sender->getName(), $this->target, $this->reason]));
    }
}