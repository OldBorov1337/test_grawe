<?php

namespace Database\Seeders;

use App\Models\Tariff;
use Illuminate\Database\Seeder;

class TariffSeeder extends Seeder
{
    public function run(): void
    {
        $tariff = Tariff::query()->updateOrCreate(
            ['id' => 15],
            [
                'name' => 'Standard life insurance',
                'base_rate' => 0.005,
                'currency' => 'EUR',
                'insurance_sum' => 30000,
                'max_insured_persons' => 10,
                'is_active' => true,
            ]
        );

        $rules = [
            [
                'type' => 'age_coefficient',
                'priority' => 10,
                'parameters' => [
                    'ranges' => [
                        ['min_age' => 0, 'max_age' => 60, 'coefficient' => 1.0],
                        ['min_age' => 61, 'max_age' => null, 'coefficient' => 1.8],
                    ],
                ],
            ],
            [
                'type' => 'group_discount',
                'priority' => 20,
                'parameters' => [
                    'min_persons' => 2,
                    'discount_percent' => 10,
                ],
            ],
        ];

        foreach ($rules as $rule) {
            $tariff->rules()->updateOrCreate(
                ['type' => $rule['type']],
                ['priority' => $rule['priority'], 'parameters' => $rule['parameters']]
            );
        }
    }
}
