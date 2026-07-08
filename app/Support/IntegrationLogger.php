<?php

namespace App\Support;

use App\Models\IntegrationLog;
use App\Models\Order;

class IntegrationLogger
{
    public static function log(array $attributes): IntegrationLog
    {
        return IntegrationLog::create([
            'direction' => $attributes['direction'],
            'channel' => $attributes['channel'],
            'event' => $attributes['event'],
            'order_id' => $attributes['order_id'] ?? null,
            'merchant_transaction_id' => $attributes['merchant_transaction_id'] ?? null,
            'reference' => $attributes['reference'] ?? null,
            'machine_id' => $attributes['machine_id'] ?? null,
            'http_method' => $attributes['http_method'] ?? null,
            'url' => isset($attributes['url']) ? substr((string) $attributes['url'], 0, 500) : null,
            'http_status' => $attributes['http_status'] ?? null,
            'success' => (bool) ($attributes['success'] ?? false),
            'duration_ms' => $attributes['duration_ms'] ?? null,
            'ip_address' => $attributes['ip_address'] ?? null,
            'message' => isset($attributes['message']) ? substr((string) $attributes['message'], 0, 1000) : null,
            'request_payload' => isset($attributes['request_payload'])
                ? static::sanitize($attributes['request_payload'])
                : null,
            'response_payload' => isset($attributes['response_payload'])
                ? static::sanitize($attributes['response_payload'])
                : null,
        ]);
    }

    public static function resolveOrderContext(?string $transactionId, ?string $machineId = null): array
    {
        if ($transactionId === null || $transactionId === '') {
            return ['order_id' => null, 'reference' => null, 'machine_id' => $machineId];
        }

        $order = Order::query()
            ->when($machineId, fn ($query) => $query->where('machine_id', $machineId))
            ->where(function ($query) use ($transactionId) {
                $query->where('machine_order_id', $transactionId)
                    ->orWhere('third_party_order_id', $transactionId);
            })
            ->first();

        return [
            'order_id' => $order?->id,
            'reference' => $order?->machine_order_id ?? $transactionId,
            'machine_id' => $order?->machine_id ?? $machineId,
        ];
    }

    public static function cellulantEventFromUrl(string $url, string $method): string
    {
        if (str_contains($url, 'oauth/token')) {
            return 'oauth_token';
        }

        if (str_contains($url, 'initiateMerchantPayment')) {
            return 'initiate_payment';
        }

        if (preg_match('#/merchants/payments/[^/?]+#', $url)) {
            return 'single_payment_lookup';
        }

        if (str_contains($url, '/merchants/payments')) {
            return 'payment_history';
        }

        if (str_contains($url, 'proxyPaymentMethods')) {
            return 'payment_methods';
        }

        if (str_contains($url, 'counterCodes')) {
            return 'counter_lookup';
        }

        return strtolower($method).'_request';
    }

    public static function sanitize(mixed $payload): mixed
    {
        if (! is_array($payload)) {
            return $payload;
        }

        $redacted = [];

        foreach ($payload as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), [
                'password',
                'secret_key',
                'sign',
                'access_token',
                'authorization',
            ], true)) {
                $redacted[$key] = '***';

                continue;
            }

            $redacted[$key] = is_array($value) ? static::sanitize($value) : $value;
        }

        return $redacted;
    }
}
