<?php

namespace App\Services\Premium\Data;

use Carbon\CarbonImmutable;

final readonly class InsuredPersonInput
{
    public function __construct(
        public string $name,
        public CarbonImmutable $birthDate,
    ) {
    }

    public function ageAt(CarbonImmutable $date): int
    {
        return (int) floor($this->birthDate->diffInYears($date, true));
    }
}
