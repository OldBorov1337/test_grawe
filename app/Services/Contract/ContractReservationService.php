<?php

namespace App\Services\Contract;

use App\Exceptions\IdempotencyConflictException;
use App\Models\Contract;
use App\Models\IdempotencyKey;
use App\Models\User;
use App\Services\Premium\Data\PersonPremium;
use App\Services\Premium\Data\PremiumCalculation;
use App\Services\Premium\PremiumCalculator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;

class ContractReservationService
{
    public function __construct(
        private readonly PremiumCalculator $calculator,
        private readonly ConnectionInterface $db,
    ) {
    }

    public function reserve(User $user, string $idempotencyKey, ReservationData $data): ReservationResult
    {
        $fingerprint = $data->fingerprint();

        $existing = $this->findExisting($user, $idempotencyKey, $fingerprint);

        if ($existing !== null) {
            return new ReservationResult($existing, false);
        }

        $calculation = $this->calculator->calculate(
            $data->tariff,
            $data->dateStart,
            $data->dateEnd,
            $data->insuranceSum,
            $data->persons,
        );

        try {
            $contract = $this->db->transaction(function () use ($user, $idempotencyKey, $fingerprint, $calculation) {
                $contract = $this->storeContract($user, $calculation);

                IdempotencyKey::query()->create([
                    'user_id' => $user->id,
                    'key' => $idempotencyKey,
                    'request_hash' => $fingerprint,
                    'contract_id' => $contract->id,
                ]);

                return $contract;
            });
        } catch (UniqueConstraintViolationException) {
            $existing = $this->findExisting($user, $idempotencyKey, $fingerprint);

            if ($existing === null) {
                throw IdempotencyConflictException::forKey($idempotencyKey);
            }

            return new ReservationResult($existing, false);
        }

        return new ReservationResult($contract->load('insuredPersons', 'tariff'), true);
    }

    private function findExisting(User $user, string $idempotencyKey, string $fingerprint): ?Contract
    {
        $record = IdempotencyKey::query()
            ->where('user_id', $user->id)
            ->where('key', $idempotencyKey)
            ->first();

        if ($record === null) {
            return null;
        }

        if (! hash_equals($record->request_hash, $fingerprint)) {
            throw IdempotencyConflictException::forKey($idempotencyKey);
        }

        return $record->contract()->with('insuredPersons', 'tariff')->firstOrFail();
    }

    private function storeContract(User $user, PremiumCalculation $calculation): Contract
    {
        $contract = $user->contracts()->create([
            'tariff_id' => $calculation->tariff->id,
            'date_start' => $calculation->dateStart,
            'date_end' => $calculation->dateEnd,
            'insurance_sum' => $calculation->insuranceSum,
            'currency' => $calculation->tariff->currency,
            'exchange_rate' => $calculation->exchangeRate->rate,
            'premium_base' => $calculation->totalBase,
            'premium_uah' => $calculation->totalUah,
            'status' => Contract::STATUS_RESERVED,
        ]);

        $contract->insuredPersons()->createMany(array_map(fn (PersonPremium $person) => [
            'name' => $person->name,
            'birth_date' => $person->birthDate,
            'age' => $person->age,
            'coefficients' => $person->coefficients,
            'premium_base' => $person->premiumBase,
            'premium_uah' => $person->premiumUah,
        ], $calculation->persons));

        return $contract;
    }
}
