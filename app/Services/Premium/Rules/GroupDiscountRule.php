<?php

namespace App\Services\Premium\Rules;

use App\Services\Premium\Data\CalculationContext;
use App\Services\Premium\Data\InsuredPersonInput;
use App\Services\Premium\PremiumRule;

class GroupDiscountRule implements PremiumRule
{
    private int $minPersons;

    private float $discountPercent;

    public function __construct(array $parameters)
    {
        $this->minPersons = (int) ($parameters['min_persons'] ?? 2);
        $this->discountPercent = (float) ($parameters['discount_percent'] ?? 0);
    }

    public function coefficient(InsuredPersonInput $person, CalculationContext $context): float
    {
        if ($context->personsCount < $this->minPersons) {
            return 1.0;
        }

        return 1 - $this->discountPercent / 100;
    }
}
