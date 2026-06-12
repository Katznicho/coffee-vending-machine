<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    protected $fillable = [
        'machine_id',
        'name',
        'location',
        'secret_key',
        'status',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'machine_id', 'machine_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
