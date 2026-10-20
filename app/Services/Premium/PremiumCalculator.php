<?php

namespace App\Services\Premium;

use App\Models\Tariff;
use App\Services\ExchangeRate\ExchangeRateProvider;
use App\Services\Premium\Data\CalculationContext;
use App\Services\Premium\Data\PersonPremium;
use App\Services\Premium\Data\PremiumCalculation;
use Carbon\CarbonImmutable;

class PremiumCalculator
{
    public function __construct(
        private readonly RuleRegistry $registry,
        private readonly ExchangeRateProvider $exchangeRates,
        private readonly int $scale = 2,
    ) {
    }

    public function calculate(
        Tariff $tariff,
        CarbonImmutable $dateStart,
        CarbonImmutable $dateEnd,
        float $insuranceSum,
        array $persons,
    ): PremiumCalculation {
        $context = new CalculationContext($tariff, $dateStart, $dateEnd, count($persons));
        $rules = $this->loadRules($tariff);
        $exchangeRate = $this->exchangeRates->getRate($tariff->currency);

        $results = [];
        $totalBase = 0.0;
        $totalUah = 0.0;

        foreach ($persons as $person) {
            $coefficients = [];
            $premium = (float) $tariff->insurance_sum * (float) $tariff->base_rate;

            foreach ($rules as $type => $rule) {
                $coefficient = $rule->coefficient($person, $context);
                $coefficients[$type] = $coefficient;
                $premium *= $coefficient;
            }

            $premiumBase = round($premium, $this->scale);
            $premiumUah = round($premiumBase * $exchangeRate->rate, $this->scale);

            $results[] = new PersonPremium(
                $person->name,
                $person->birthDate,
                $person->ageAt($dateStart),
                $coefficients,
                $premiumBase,
                $premiumUah,
            );

            $totalBase += $premiumBase;
            $totalUah += $premiumUah;
        }

        return new PremiumCalculation(
            $tariff,
            $dateStart,
            $dateEnd,
            $insuranceSum,
            $exchangeRate,
            $results,
            round($totalBase, $this->scale),
            round($totalUah, $this->scale),
        );
    }

    private function loadRules(Tariff $tariff): array
    {
        $rules = [];

        foreach ($tariff->rules()->orderBy('priority')->get() as $rule) {
            $rules[$rule->type] = $this->registry->make($rule);
        }

        return $rules;
    }
}
