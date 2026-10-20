<?php

namespace App\Http\Resources;

use App\Models\InsuredPerson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'tariff' => [
                'id' => $this->tariff->id,
                'name' => $this->tariff->name,
            ],
            'date_start' => $this->date_start->toDateString(),
            'date_end' => $this->date_end->toDateString(),
            'insurance_sum' => (float) $this->insurance_sum,
            'currency' => $this->currency,
            'exchange_rate' => (float) $this->exchange_rate,
            'premium' => (float) $this->premium_base,
            'premium_uah' => (float) $this->premium_uah,
            'insured_persons' => $this->insuredPersons->map(fn (InsuredPerson $person) => [
                'id' => $person->id,
                'name' => $person->name,
                'birth_date' => $person->birth_date->toDateString(),
                'age' => $person->age,
                'coefficients' => $person->coefficients,
                'premium' => (float) $person->premium_base,
                'premium_uah' => (float) $person->premium_uah,
            ])->all(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
