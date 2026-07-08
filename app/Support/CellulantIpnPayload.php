<?php

namespace App\Support;

class CellulantIpnPayload
{
    public const ACK_STATUS_CODE = '188';

    public static function paymentStatusCode(array $payload): ?string
    {
        foreach ([
            data_get($payload, 'requestStatusCode'),
            data_get($payload, 'payload.packet.statusCode'),
            data_get($payload, 'statusCode'),
            data_get($payload, 'status_code'),
        ] as $code) {
            if ($code === null || $code === '') {
                continue;
            }

            $code = (string) $code;

            if ($code === self::ACK_STATUS_CODE) {
                continue;
            }

            return $code;
        }

        return null;
    }

    public static function paymentStatusDescription(array $payload): ?string
    {
        $description = data_get($payload, 'requestStatusDescription')
            ?? data_get($payload, 'statusDescription')
            ?? data_get($payload, 'payload.packet.statusDescription');

        return filled($description) ? (string) $description : null;
    }
}
