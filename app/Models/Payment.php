<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'phone_number',
        'provider',
        'transaction_id',
        'reference',
        'amount',
        'status',
        'provider_response',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'provider_response' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
