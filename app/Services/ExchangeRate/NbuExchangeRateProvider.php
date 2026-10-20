<?php

namespace App\Services\ExchangeRate;

use App\Exceptions\ExchangeRateUnavailableException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\RequestException;
use Throwable;

class NbuExchangeRateProvider implements ExchangeRateProvider
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $baseUrl,
        private readonly int $timeout = 10,
    ) {
    }

    public function getRate(string $currency): ExchangeRate
    {
        $currency = strtoupper($currency);

        try {
            $response = $this->http
                ->timeout($this->timeout)
                ->acceptJson()
                ->get($this->baseUrl, ['valcode' => $currency, 'json' => ''])
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            throw ExchangeRateUnavailableException::forCurrency($currency, $e);
        }

        $item = collect($response->json())
            ->first(fn ($row) => is_array($row) && strtoupper((string) ($row['cc'] ?? '')) === $currency);

        if (! is_array($item) || ! isset($item['rate']) || (float) $item['rate'] <= 0) {
            throw ExchangeRateUnavailableException::forCurrency($currency);
        }

        try {
            $date = CarbonImmutable::createFromFormat('d.m.Y', (string) ($item['exchangedate'] ?? ''))->startOfDay();
        } catch (Throwable) {
            $date = CarbonImmutable::today();
        }

        return new ExchangeRate($currency, (float) $item['rate'], $date);
    }
}
