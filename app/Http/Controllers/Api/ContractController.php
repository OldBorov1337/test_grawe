<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContractRequest;
use App\Http\Resources\PremiumCalculationResource;
use App\Services\Premium\PremiumCalculator;

class ContractController extends Controller
{
    public function __construct(private readonly PremiumCalculator $calculator)
    {
    }

    public function calculate(ContractRequest $request): PremiumCalculationResource
    {
        $calculation = $this->calculator->calculate(
            $request->tariff(),
            $request->dateStart(),
            $request->dateEnd(),
            $request->insuranceSum(),
            $request->insuredPersons(),
        );

        return new PremiumCalculationResource($calculation);
    }
}
