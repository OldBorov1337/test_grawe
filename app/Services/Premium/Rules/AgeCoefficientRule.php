<?php

namespace App\Services\Premium\Rules;

use App\Exceptions\InvalidTariffRuleException;
use App\Services\Premium\Data\CalculationContext;
use App\Services\Premium\Data\InsuredPersonInput;
use App\Services\Premium\PremiumRule;

class AgeCoefficientRule implements PremiumRule
{
    private array $ranges;

    public function __construct(array $parameters)
    {
        $ranges = $parameters['ranges'] ?? null;

        if (! is_array($ranges) || $ranges === []) {
            throw InvalidTariffRuleException::invalidParameters('age_coefficient', 'ranges are missing');
        }

        $this->ranges = $ranges;
    }

    public function coefficient(InsuredPersonInput $person, CalculationContext $context): float
    {
        $age = $person->ageAt($context->dateStart);

        foreach ($this->ranges as $range) {
            $min = $range['min_age'] ?? 0;
            $max = $range['max_age'] ?? null;

            if ($age >= $min && ($max === null || $age <= $max)) {
                return (float) $range['coefficient'];
            }
        }

        throw InvalidTariffRuleException::invalidParameters('age_coefficient', "no range covers age {$age}");
    }
}
