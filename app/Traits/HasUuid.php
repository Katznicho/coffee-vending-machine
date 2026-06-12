<?php

namespace App\Traits;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Builder;

trait HasUuid
{
    /**
     * Boot the trait.
     */
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Uuid::uuid4();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Retrieve the model for route model binding by UUID.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        return $this->where($field, $value)->firstOrFail();
    }

    /**
     * Scope a query to only include records with a specific UUID.
     */
    public function scopeWhereUuid(Builder $query, string $uuid): Builder
    {
        return $query->where('uuid', $uuid);
    }

    /**
     * Find a model by UUID.
     */
    public static function findByUuid(string $uuid)
    {
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Find a model by UUID or fail.
     */
    public static function findByUuidOrFail(string $uuid)
    {
        return static::where('uuid', $uuid)->firstOrFail();
    }
}