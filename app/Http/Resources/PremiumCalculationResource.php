<?php

namespace App\Http\Resources;

use App\Services\Premium\Data\PersonPremium;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PremiumCalculationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tariff' => [
                'id' => $this->tariff->id,
                'name' => $this->tariff->name,
                'currency' => $this->tariff->currency,
                'insurance_sum' => (float) $this->tariff->insurance_sum,
                'base_rate' => (float) $this->tariff->base_rate,
            ],
            'date_start' => $this->dateStart->toDateString(),
            'date_end' => $this->dateEnd->toDateString(),
            'insurance_sum' => $this->insuranceSum,
            'exchange_rate' => [
                'currency' => $this->exchangeRate->currency,
                'rate' => $this->exchangeRate->rate,
                'date' => $this->exchangeRate->date->toDateString(),
            ],
            'insured_persons' => array_map(fn (PersonPremium $person) => [
                'name' => $person->name,
                'birth_date' => $person->birthDate->toDateString(),
                'age' => $person->age,
                'coefficients' => $person->coefficients,
                'premium' => $person->premiumBase,
                'premium_uah' => $person->premiumUah,
            ], $this->persons),
            'total_premium' => $this->totalBase,
            'total_premium_uah' => $this->totalUah,
        ];
    }
}
