<?php

namespace ProjectInfinity\PocketBans\database;

use ProjectInfinity\PocketBans\data\Ban;

abstract class DataProvider {

    abstract public function close(): void;

    /** Ban related */
    abstract public function getBans(): array;
    abstract public function getBan(string $player): ?Ban;
    abstract public function ban(Ban $ban): bool;

    abstract public function storeXuid(string $player, string $xuid): void;
}