<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class CellulantSetting extends Model
{
    protected $fillable = [
        'environment',
        'sandbox_base_url',
        'sandbox_username',
        'sandbox_password',
        'sandbox_counter_code',
        'production_base_url',
        'production_username',
        'production_password',
        'production_counter_code',
        'initiate_payment_path',
        'default_payer_client_code',
        'airtel_payer_client_code',
        'auto_detect_payer',
        'country_code',
        'currency_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'auto_detect_payer' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return Cache::remember('cellulant_settings', 300, function () {
            return static::query()->firstOrCreate([], static::defaults());
        });
    }

    public static function defaults(): array
    {
        return [
            'environment' => 'sandbox',
            'sandbox_base_url' => 'https://payments-instore.sandbox.tingg.africa',
            'sandbox_username' => 'pat_sanboxAPI_user',
            'sandbox_password' => 'pzBkMynNoAHcmK1c2ATzNtN7iJ8jo8Qw',
            'sandbox_counter_code' => '1008',
            'production_base_url' => 'https://payments.instore.tingg.africa',
            'initiate_payment_path' => '/initiateMerchantPayment',
            'default_payer_client_code' => 'MTNUG',
            'airtel_payer_client_code' => 'AIRTELUG',
            'auto_detect_payer' => true,
            'country_code' => 'UGA',
            'currency_code' => 'UGX',
            'is_active' => true,
        ];
    }

    public static function clearCache(): void
    {
        Cache::forget('cellulant_settings');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
    }

    public function isSandbox(): bool
    {
        return $this->environment === 'sandbox';
    }

    public function activeBaseUrl(): string
    {
        $url = $this->isSandbox()
            ? $this->sandbox_base_url
            : $this->production_base_url;

        return rtrim((string) $url, '/');
    }

    public function activeUsername(): ?string
    {
        return $this->isSandbox()
            ? $this->sandbox_username
            : $this->production_username;
    }

    public function activePassword(): ?string
    {
        $encrypted = $this->isSandbox()
            ? $this->sandbox_password
            : $this->production_password;

        if (! $encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return $encrypted;
        }
    }

    public function activeCounterCode(): ?string
    {
        return $this->isSandbox()
            ? $this->sandbox_counter_code
            : $this->production_counter_code;
    }

    public function setSandboxPasswordAttribute(?string $value): void
    {
        if ($value !== null && $value !== '') {
            $this->attributes['sandbox_password'] = Crypt::encryptString($value);
        }
    }

    public function setProductionPasswordAttribute(?string $value): void
    {
        if ($value !== null && $value !== '') {
            $this->attributes['production_password'] = Crypt::encryptString($value);
        }
    }
}
