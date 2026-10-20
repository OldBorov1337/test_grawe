<?php

namespace App\Providers;

use App\Services\ExchangeRate\ExchangeRateProvider;
use App\Services\ExchangeRate\NbuExchangeRateProvider;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
