<?php

namespace ProjectInfinity\PocketBans\util;

class ToolBox {

    public static function xuidQuickSort(array $arr): array {
        $length = \count($arr);

        if($length <= 1) return $arr;

        $pivot = $arr[0];

        $left = [];
        $right = [];

        // Loop and compare each item to the pivot value before placing them in the correct partition.
        for($i = 1; $i < $length; $i++) {
            if($arr[$i]->score > $pivot->score) {
                $left[] = $arr[$i];
            } else {
                $right[] = $arr[$i];
            }
        }

        return array_merge(... [self::xuidQuickSort($left), [$pivot], self::xuidQuickSort($right)]);
    }

}