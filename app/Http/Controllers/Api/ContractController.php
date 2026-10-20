<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContractRequest;
use App\Http\Requests\ReserveContractRequest;
use App\Http\Resources\ContractResource;
use App\Http\Resources\PremiumCalculationResource;
use App\Models\Contract;
use App\Services\Contract\ContractReservationService;
use App\Services\Contract\ReservationData;
use App\Services\Premium\PremiumCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContractController extends Controller
{
    public function __construct(
        private readonly PremiumCalculator $calculator,
        private readonly ContractReservationService $reservations,
    ) {
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

    public function reserve(ReserveContractRequest $request): JsonResponse
    {
        $data = new ReservationData(
            $request->tariff(),
            $request->dateStart(),
            $request->dateEnd(),
            $request->insuranceSum(),
            $request->insuredPersons(),
        );

        $result = $this->reservations->reserve($request->user(), $request->idempotencyKey(), $data);

        return (new ContractResource($result->contract))
            ->response()
            ->setStatusCode($result->created ? 201 : 200);
    }

    public function show(Request $request, Contract $contract): ContractResource
    {
        Gate::authorize('view', $contract);

        return new ContractResource($contract->load('insuredPersons', 'tariff'));
    }
}
