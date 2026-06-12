<?php

namespace App\Support;

use Illuminate\Support\Str;

class OrderReference
{
    public static function thirdPartyId(): string
    {
        return 'RF-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    public static function marzPayReference(): string
    {
        return (string) Str::uuid();
    }
}
