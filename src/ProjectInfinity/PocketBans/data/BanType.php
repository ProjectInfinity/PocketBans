<?php

namespace ProjectInfinity\PocketBans\data;

class BanType {
    public const NO_BAN = 0;
    public const TEMP = 1;
    public const LOCAL = 2;
    public const GLOBAL = 3;

    public static function getNameByValue($value) {
        $class = new \ReflectionClass(__CLASS__);
        $constants = $class->getConstants();
        foreach($constants as $key => $val) {
            if($value === $val) return $key;
        }
        return null;
    }
}