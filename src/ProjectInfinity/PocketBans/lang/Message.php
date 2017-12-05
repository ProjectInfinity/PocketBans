<?php

namespace ProjectInfinity\PocketBans\lang;

use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Message {

    /** @var Config $messages */
    private static $messages;
    /** @var Config $fallbackMessages */
    private static $fallbackMessages;
    /** @var array $colors */
    private static $colors;
    private static $cache;

    public static function setMessages(Config $messages, ?Config $fallbackMessages): void {
        self::$cache = [];
        self::$messages = $messages;
        self::$fallbackMessages = $fallbackMessages;
        self::$colors = (new \ReflectionClass(TextFormat::class))->getConstants();
    }

    private static function parseColors($message): string {
        $msg = $message;
        foreach(self::$colors as $color => $value) {
            $key = '%'.strtolower($color).'%';
            if(strpos($msg, $key) !== false) {
                $msg = str_replace($key, $value, $msg);
            }
        }
        return $msg;
    }

    private static function prepareMessage($messageKey): string {
        $message = self::$cache[$messageKey] ?? null;
        if($message === null) {
            $message = self::parseColors(self::$messages->getNested($messageKey) ?? self::$fallbackMessages->getNested($messageKey, '%red%** PocketBans: Missing message for key '.$messageKey.' **'));
            self::$cache[$messageKey] = $message;
        }
        return $message;
    }

    public static function send(CommandSender $sender, string $messageKey, $args = null): void {
        $message = self::prepareMessage($messageKey);
        if($args !== null) {
            $sender->sendMessage(vsprintf($message, $args));
        } else {
            $sender->sendMessage($message);
        }
    }

    public static function get(string $messageKey, $args = null): string {
        if($args !== null) return vsprintf(self::prepareMessage($messageKey), $args);
        return self::prepareMessage($messageKey);
    }
}