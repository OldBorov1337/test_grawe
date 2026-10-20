<?php

namespace App\Services\Premium\Data;

use App\Models\Tariff;
use App\Services\ExchangeRate\ExchangeRate;
use Carbon\CarbonImmutable;

final readonly class PremiumCalculation
{
    public function __construct(
        public Tariff $tariff,
        public CarbonImmutable $dateStart,
        public CarbonImmutable $dateEnd,
        public float $insuranceSum,
        public ExchangeRate $exchangeRate,
        public array $persons,
        public float $totalBase,
        public float $totalUah,
    ) {
    }
}
