<?php

namespace App\Services\Premium\Data;

use App\Models\Tariff;
use Carbon\CarbonImmutable;

final readonly class CalculationContext
{
    public function __construct(
        public Tariff $tariff,
        public CarbonImmutable $dateStart,
        public CarbonImmutable $dateEnd,
        public int $personsCount,
    ) {
    }
}
