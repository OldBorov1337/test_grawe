<?php

namespace App\Services\ExchangeRate;

use Carbon\CarbonImmutable;

final readonly class ExchangeRate
{
    public function __construct(
        public string $currency,
        public float $rate,
        public CarbonImmutable $date,
    ) {
    }
}
