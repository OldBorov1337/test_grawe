<?php

namespace App\Providers;

use App\Services\ExchangeRate\ExchangeRateProvider;
use App\Services\ExchangeRate\NbuExchangeRateProvider;
use App\Services\Premium\PremiumCalculator;
use App\Services\Premium\RuleRegistry;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ExchangeRateProvider::class, function ($app) {
            return new NbuExchangeRateProvider(
                $app->make(HttpClient::class),
                config('services.nbu.exchange_url'),
                config('services.nbu.timeout'),
            );
        });

        $this->app->singleton(RuleRegistry::class, function () {
            return new RuleRegistry(config('premium.rules', []));
        });

        $this->app->bind(PremiumCalculator::class, function ($app) {
            return new PremiumCalculator(
                $app->make(RuleRegistry::class),
                $app->make(ExchangeRateProvider::class),
                (int) config('premium.scale', 2),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
