<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationLog extends Model
{
    protected $fillable = [
        'direction',
        'channel',
        'event',
        'order_id',
        'merchant_transaction_id',
        'reference',
        'machine_id',
        'http_method',
        'url',
        'http_status',
        'success',
        'duration_ms',
        'ip_address',
        'message',
        'request_payload',
        'response_payload',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'http_status' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
