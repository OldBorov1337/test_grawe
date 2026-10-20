<?php

namespace App\Services\Premium;

use App\Services\Premium\Data\CalculationContext;
use App\Services\Premium\Data\InsuredPersonInput;

interface PremiumRule
{
    public function __construct(array $parameters);

    public function coefficient(InsuredPersonInput $person, CalculationContext $context): float;
}
