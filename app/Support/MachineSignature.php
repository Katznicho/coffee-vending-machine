<?php

namespace App\Support;

class MachineSignature
{
    public static function generate(string $appKey, string $timestamp, string $randstr): string
    {
        $values = [$appKey, $timestamp, $randstr];
        sort($values, SORT_STRING);

        return sha1(implode('', $values));
    }

    public static function verify(string $appKey, string $timestamp, string $randstr, string $sign): bool
    {
        return hash_equals(self::generate($appKey, $timestamp, $randstr), $sign);
    }
}
