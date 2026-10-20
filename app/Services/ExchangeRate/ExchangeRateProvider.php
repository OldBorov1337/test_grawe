<?php

namespace App\Services\ExchangeRate;

interface ExchangeRateProvider
{
    public function getRate(string $currency): ExchangeRate;
}
