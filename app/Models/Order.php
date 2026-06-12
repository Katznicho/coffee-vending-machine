<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'machine_order_id',
        'third_party_order_id',
        'machine_id',
        'track_no',
        'product_name',
        'amount',
        'channel_id',
        'payment_status',
        'dispense_status',
        'customer_phone',
        'paid_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'machine_id', 'machine_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function markExpiredIfNeeded(): void
    {
        if ($this->payment_status === 'pending' && $this->isExpired()) {
            $this->update(['payment_status' => 'expired']);
        }
    }

    public function vendingStatusCode(): string
    {
        $this->markExpiredIfNeeded();

        return match ($this->payment_status) {
            'paid' => '1',
            'pending' => '2',
            'expired' => '3',
            'failed', 'refunded' => '0',
            default => '2',
        };
    }

    public function vendingStatusMessage(): string
    {
        $this->markExpiredIfNeeded();

        return match ($this->payment_status) {
            'paid' => 'Success',
            'pending' => 'Waiting payment',
            'expired' => 'Expired',
            'failed' => 'Failed',
            'refunded' => 'Closed',
            default => 'Waiting payment',
        };
    }
}
