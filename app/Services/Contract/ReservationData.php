<?php

namespace App\Services\Contract;

use App\Models\Tariff;
use App\Services\Premium\Data\InsuredPersonInput;
use Carbon\CarbonImmutable;

final readonly class ReservationData
{
    public function __construct(
        public Tariff $tariff,
        public CarbonImmutable $dateStart,
        public CarbonImmutable $dateEnd,
        public float $insuranceSum,
        public array $persons,
    ) {
    }

    public function fingerprint(): string
    {
        $payload = [
            'tariff_id' => $this->tariff->id,
            'date_start' => $this->dateStart->toDateString(),
            'date_end' => $this->dateEnd->toDateString(),
            'insurance_sum' => $this->insuranceSum,
            'insured_persons' => array_map(fn (InsuredPersonInput $person) => [
                'name' => $person->name,
                'birth_date' => $person->birthDate->toDateString(),
            ], $this->persons),
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
