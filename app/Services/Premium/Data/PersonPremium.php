<?php

namespace App\Services\Premium\Data;

use Carbon\CarbonImmutable;

final readonly class PersonPremium
{
    public function __construct(
        public string $name,
        public CarbonImmutable $birthDate,
        public int $age,
        public array $coefficients,
        public float $premiumBase,
        public float $premiumUah,
    ) {
    }
}
