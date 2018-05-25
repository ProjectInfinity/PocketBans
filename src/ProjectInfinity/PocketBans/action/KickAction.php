<?php

namespace ProjectInfinity\PocketBans\action;

use pocketmine\command\CommandSender;
use ProjectInfinity\PocketBans\lang\Message;
use ProjectInfinity\PocketBans\permission\Permissions;
use ProjectInfinity\PocketBans\PocketBans;

class KickAction {

    private $plugin;

    private $target;
    private $reason;
    private $sender;
    private $kickAll;

    public function __construct(string $target, ?string $reason, CommandSender $sender) {
        $this->plugin = PocketBans::getPlugin();

        $this->target = $target;
        $this->reason = $reason;
        $this->sender = $sender;
        $this->kickAll = $target === '*';

        $this->run();
    }

    private function run(): void {
        # Check if the player is online before continuing.
        if(!$this->kickAll && $this->plugin->getServer()->getPlayer($this->target) === null) {
            Message::send($this->sender, 'kick.player_offline');
            return;
        }

        if($this->kickAll) {
            $kicked = 0;
            foreach($this->plugin->getServer()->getOnlinePlayers() as $player) {
                # Avoid kicking the sender.
                if($player->getName() === $this->sender->getName()) continue;
                # Do not kick the player if they are exempted from kicks.
                if($player->hasPermission(Permissions::EXEMPT_KICK)) {
                    Message::send($this->sender, 'exempt.kick', [$player->getName()]);
                    continue;
                }
                $player->kick(Message::get('kick.message', $this->reason ?? Message::get('no_reason')), false);
                $kicked++;
                # TODO: Figure out if we have to send our own kick event here.
            }
            Message::send($this->sender, 'kick.result_multi', [$kicked]);
            return;
        }
        $target = $this->plugin->getServer()->getPlayer($this->target);
        if($target === null) {
            Message::send($this->sender, 'kick.player_offline');
            return;
        }
        $targetName = $target->getName();
        $target->kick(Message::get('kick.message', $this->reason ?? Message::get('no_reason')), false);
        $this->plugin->getServer()->broadcastMessage(Message::get('kick.broadcast', [$this->sender->getName(), $targetName, $this->reason ?? Message::get('no_reason')]));
        # TODO: Figure out if we have to send our own kick event here.
    }
}