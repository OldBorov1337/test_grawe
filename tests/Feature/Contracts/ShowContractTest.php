<?php

namespace Tests\Feature\Contracts;

use App\Models\Contract;
use App\Models\User;

class ShowContractTest extends ContractTestCase
{
    private function createContractFor(User $user): Contract
    {
        $contract = $user->contracts()->create([
            'tariff_id' => 15,
            'date_start' => '2026-11-01',
            'date_end' => '2027-10-31',
            'insurance_sum' => 100000,
            'currency' => 'EUR',
            'exchange_rate' => self::RATE,
            'premium_base' => 378,
            'premium_uah' => 19100.37,
        ]);

        $contract->insuredPersons()->createMany([
            ['name' => 'Ivan', 'birth_date' => '1995-05-10', 'age' => 31, 'coefficients' => ['age_coefficient' => 1.0, 'group_discount' => 0.9], 'premium_base' => 135, 'premium_uah' => 6821.56],
            ['name' => 'Olena', 'birth_date' => '1960-03-15', 'age' => 66, 'coefficients' => ['age_coefficient' => 1.8, 'group_discount' => 0.9], 'premium_base' => 243, 'premium_uah' => 12278.81],
        ]);

        return $contract;
    }

    public function test_it_requires_authentication(): void
    {
        $contract = $this->createContractFor(User::factory()->create());

        $this->getJson("/api/contracts/{$contract->id}")->assertUnauthorized();
    }

    public function test_owner_can_view_contract(): void
    {
        $user = $this->actingAsUser();
        $contract = $this->createContractFor($user);

        $this->getJson("/api/contracts/{$contract->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $contract->id)
            ->assertJsonPath('data.status', 'reserved')
            ->assertJsonPath('data.tariff.id', 15)
            ->assertJsonPath('data.premium', 378)
            ->assertJsonPath('data.premium_uah', 19100.37)
            ->assertJsonCount(2, 'data.insured_persons')
            ->assertJsonPath('data.insured_persons.1.name', 'Olena')
            ->assertJsonPath('data.insured_persons.1.coefficients.age_coefficient', 1.8);
    }

    public function test_other_user_cannot_view_contract(): void
    {
        $contract = $this->createContractFor(User::factory()->create());
        $this->actingAsUser();

        $this->getJson("/api/contracts/{$contract->id}")->assertForbidden();
    }

    public function test_missing_contract_returns_json_404(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/contracts/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Resource not found.');

        $this->getJson('/api/contracts/abc')
            ->assertNotFound()
            ->assertJsonPath('message', 'Resource not found.');
    }
}
