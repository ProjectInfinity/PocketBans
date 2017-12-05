<?php

namespace ProjectInfinity\PocketBans\lang;

use pocketmine\utils\Config;
use ProjectInfinity\PocketBans\PocketBans;

class MessageHandler {

    private static $iso;
    /** @var Config $messages */
    private static $messages;
    /** @var Config $fallbackMessages */
    private static $fallbackMessages;

    public static function init(): void {
        self::$iso = strtolower(PocketBans::getPlugin()->getConfig()->get('language', 'en'));
        self::loadLanguageFile();
        Message::setMessages(self::$messages, self::$fallbackMessages ?? null);
    }

    public static function loadLanguageFile(): void {
        # Check if the language file exists, if it does not fall back to English.
        if(PocketBans::getPlugin()->getResource('lang_'.self::$iso.'.yml') === null) {
            PocketBans::getPlugin()->getLogger()->warning('The specified language "'.self::$iso.'" is not a supported language. Falling back to English.');
            self::$iso = 'en';
        }

        PocketBans::getPlugin()->saveResource('lang_'.self::$iso.'.yml', true);
        self::$messages = new Config(PocketBans::getPlugin()->getDataFolder().'lang_'.self::$iso.'.yml', Config::YAML);
        if(self::$iso !== 'en') {
            PocketBans::getPlugin()->saveResource('lang_en.yml', true);
            self::$fallbackMessages = new Config(PocketBans::getPlugin()->getDataFolder().'lang_en.yml', Config::YAML);
        }
    }

}