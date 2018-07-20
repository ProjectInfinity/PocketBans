<?php

namespace ProjectInfinity\PocketBans\action;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use ProjectInfinity\PocketBans\lang\Message;
use ProjectInfinity\PocketBans\permission\PermissionHandler;
use ProjectInfinity\PocketBans\PocketBans;

class BanAction {

    private $plugin;
    private $targetPlayer;
    private $targetXuid;
    private $reason;
    private $type;
    private $sender;
    private $manager;

    public function __construct(Player $targetPlayer, String $xuid, ?string $reason, int $type, CommandSender $sender) {
        $this->plugin = PocketBans::getPlugin();
        $this->manager = $this->plugin->getBanManager();
        $this->targetPlayer = $targetPlayer;
        $this->targetXuid = $xuid;
        $this->reason = $reason ?? Message::get('no_reason');
        $this->type = $type;
        $this->sender = $sender;

        $this->run();
    }

    private function run(): void {
        # Kick the player if online and show a ban message.
        if($this->targetPlayer->isOnline()) {
            # Check if the player is exempted before continuing.
            if(PermissionHandler::isExempted($this->targetPlayer, true)) {
                Message::send($this->sender, 'exempt.ban', [$this->targetPlayer->getName()]);
                return;
            }
            $this->targetPlayer->kick(Message::get('ban.message', [$this->reason]), false);
        }

        if(!$this->manager->banPlayer($this->targetPlayer->getName(), $this->reason, $this->targetXuid, $this->type, $this->sender->getName())) {
            Message::send($this->sender, 'ban.failure', [$this->targetPlayer->getName()]);
            return;
        }

        # TODO: Figure out if we have to send our own ban event here.
        $this->plugin->getServer()->broadcastMessage(Message::get('ban.broadcast', [$this->sender->getName(), $this->targetPlayer->getName(), $this->reason]));
    }
}