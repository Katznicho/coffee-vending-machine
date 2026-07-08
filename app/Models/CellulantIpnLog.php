<?php

namespace App\Models;

use App\Support\CellulantIpnPayload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CellulantIpnLog extends Model
{
    protected $fillable = [
        'order_id',
        'merchant_transaction_id',
        'reference',
        'status_code',
        'msisdn',
        'amount',
        'ip_address',
        'request_payload',
        'response_payload',
        'order_matched',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'order_matched' => 'boolean',
            'amount' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public static function fromRequest(array $payload, array $response, ?Order $order, ?string $ipAddress): self
    {
        return static::create([
            'order_id' => $order?->id,
            'merchant_transaction_id' => (string) (
                data_get($payload, 'merchantTransactionID')
                ?? data_get($payload, 'merchant_transaction_id')
                ?? ''
            ) ?: null,
            'reference' => (string) (
                data_get($payload, 'reference')
                ?? data_get($payload, 'extraData.reference')
                ?? ''
            ) ?: null,
            'status_code' => CellulantIpnPayload::paymentStatusCode($payload),
            'msisdn' => (string) (data_get($payload, 'msisdn') ?? '') ?: null,
            'amount' => is_numeric(data_get($payload, 'amount')) ? (int) data_get($payload, 'amount') : null,
            'ip_address' => $ipAddress,
            'request_payload' => $payload,
            'response_payload' => $response,
            'order_matched' => $order !== null,
        ]);
    }

    public function paymentStatusCode(): ?string
    {
        return CellulantIpnPayload::paymentStatusCode($this->request_payload ?? [])
            ?? (($this->status_code !== null && $this->status_code !== CellulantIpnPayload::ACK_STATUS_CODE)
                ? $this->status_code
                : null);
    }

    public function paymentStatusDescription(): ?string
    {
        return CellulantIpnPayload::paymentStatusDescription($this->request_payload ?? []);
    }
}
