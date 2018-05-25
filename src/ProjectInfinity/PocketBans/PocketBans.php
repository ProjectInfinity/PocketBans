<?php

namespace ProjectInfinity\PocketBans;

use pocketmine\plugin\PluginBase;
use ProjectInfinity\PocketBans\cmd\BanCommand;
use ProjectInfinity\PocketBans\cmd\KickCommand;
use ProjectInfinity\PocketBans\lang\MessageHandler;
use ProjectInfinity\PocketBans\util\BanManager;

class PocketBans extends PluginBase {

    /** @var PocketBans $plugin */
    private static $plugin;
    /** @var BanManager $banManager */
    private $banManager;

    public static $dev, $cert;

    public function onEnable() {
        if(!$this->getServer()->getOnlineMode()) {
            $this->getLogger()->critical('PocketBans is intended for online-mode/xbox-auth servers ONLY!');
            $this->getPluginLoader()->disablePlugin($this);
            return;
        }
        self::$plugin = $this;
        $this->saveDefaultConfig();
        self::$dev = $this->getConfig()->get('dev') === true;

        # Save and load certificates.
        self::$cert = $this->getDataFolder().'cacert.pem';
        if(!file_exists(self::$cert)) {

            $this->getLogger()->warning('Could not find cacert.pem, downloading it now.');

            $curl = curl_init('https://curl.haxx.se/ca/cacert.pem');

            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_PORT => 443,
                CURLOPT_HEADER => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CONNECTTIMEOUT => 10
            ]);

            $res = curl_exec($curl);

            if($res === false) {
                $this->getLogger()->critical(curl_error($curl));
                $this->getLogger()->critical(curl_errno($curl));
            } else {
                $this->getLogger()->info('Downloading cURL root CA.');
                $file = fopen(self::$cert, 'wb+');
                fwrite($file, $res);
                fclose($file);
                $this->getLogger()->info('Finished downloading cURL root CA.');
            }

            curl_close($curl);
        }

        MessageHandler::init();
        $this->banManager = new BanManager($this);

        # Unregister default commands.
        $this->unregisterCommands([
            'kick',
            'ban',
            'pardon',
            'ban-ip',
            'pardon-ip'
        ]);

        # Register our commands.
        $this->getServer()->getCommandMap()->register('pb', new KickCommand($this));
        $this->getServer()->getCommandMap()->register('pb', new BanCommand($this));
    }

    public function onDisable() {
        self::$plugin = null;
        self::$dev = null;
        self::$cert = null;
        unset($this->banManager);
    }

    private function unregisterCommands(array $commands) {
        foreach($commands as $command) {
            $commandMap = $this->getServer()->getCommandMap();
            $cmd = $commandMap->getCommand($command);
            if($cmd === null) return;
            $cmd->setLabel($command.'_disabled');
            $cmd->unregister($commandMap);
        }
    }

    public static function getPlugin(): PocketBans {
        return self::$plugin;
    }

    public function getBanManager(): BanManager {
        return $this->banManager;
    }
}