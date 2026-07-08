<?php

namespace App\Providers;

use App\Services\PaymentProviders\CellulantApiClient;
use App\Services\PaymentProviders\CellulantProvider;
use App\Services\PaymentProviders\PaymentProviderInterface;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CellulantApiClient::class, fn () => CellulantApiClient::forCurrent());
        $this->app->bind(PaymentProviderInterface::class, CellulantProvider::class);
    }

    public function boot(): void
    {
        FilamentColor::register([
            'primary' => Color::Red,
        ]);

        if ($appUrl = config('app.url')) {
            \Illuminate\Support\Facades\URL::forceRootUrl($appUrl);
        }
    }
}
