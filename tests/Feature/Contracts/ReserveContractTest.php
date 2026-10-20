<?php

namespace Tests\Feature\Contracts;

use App\Models\Contract;
use App\Models\IdempotencyKey;
use App\Models\InsuredPerson;
use App\Models\User;
use RuntimeException;

class ReserveContractTest extends ContractTestCase
{
    private function reserve(array $payload, string $key = 'key-1')
    {
        return $this->withHeader('Idempotency-Key', $key)->postJson('/api/contracts/reserve', $payload);
    }

    public function test_it_requires_authentication(): void
    {
        $this->reserve($this->payload())->assertUnauthorized();
    }

    public function test_it_requires_idempotency_key_header(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/contracts/reserve', $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idempotency_key');

        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_it_reserves_contract_with_insured_persons(): void
    {
        $user = $this->actingAsUser();

        $response = $this->reserve($this->payload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'reserved')
            ->assertJsonPath('data.premium', 378)
            ->assertJsonPath('data.exchange_rate', self::RATE)
            ->assertJsonCount(2, 'data.insured_persons');

        $contract = Contract::query()->findOrFail($response->json('data.id'));

        $this->assertSame($user->id, $contract->user_id);
        $this->assertSame(15, $contract->tariff_id);
        $this->assertSame('100000.00', $contract->insurance_sum);
        $this->assertSame(2, $contract->insuredPersons()->count());
        $this->assertDatabaseHas('insured_persons', ['contract_id' => $contract->id, 'name' => 'Olena', 'age' => 66]);
        $this->assertDatabaseHas('idempotency_keys', ['user_id' => $user->id, 'key' => 'key-1', 'contract_id' => $contract->id]);
    }

    public function test_user_id_from_payload_is_ignored(): void
    {
        $user = $this->actingAsUser();
        $other = User::factory()->create();

        $response = $this->reserve($this->payload(['user_id' => $other->id]))->assertCreated();

        $this->assertSame($user->id, Contract::query()->findOrFail($response->json('data.id'))->user_id);
    }

    public function test_repeated_request_with_same_key_returns_existing_contract(): void
    {
        $this->actingAsUser();

        $first = $this->reserve($this->payload())->assertCreated();
        $second = $this->reserve($this->payload())->assertOk();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('contracts', 1);
        $this->assertDatabaseCount('insured_persons', 2);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }

    public function test_same_key_with_different_payload_returns_conflict(): void
    {
        $this->actingAsUser();

        $this->reserve($this->payload())->assertCreated();

        $this->reserve($this->payload(['insurance_sum' => 90000]))
            ->assertStatus(409)
            ->assertJsonStructure(['message', 'errors' => ['idempotency_key']]);

        $this->assertDatabaseCount('contracts', 1);
    }

    public function test_different_keys_create_separate_contracts(): void
    {
        $this->actingAsUser();

        $this->reserve($this->payload(), 'key-1')->assertCreated();
        $this->reserve($this->payload(), 'key-2')->assertCreated();

        $this->assertDatabaseCount('contracts', 2);
    }

    public function test_idempotency_keys_are_scoped_per_user(): void
    {
        $this->actingAsUser();
        $this->reserve($this->payload())->assertCreated();

        $this->actingAsUser(User::factory()->create());
        $this->reserve($this->payload())->assertCreated();

        $this->assertDatabaseCount('contracts', 2);
        $this->assertDatabaseCount('idempotency_keys', 2);
    }

    public function test_nothing_is_stored_when_persons_cannot_be_saved(): void
    {
        $this->actingAsUser();
        $this->withoutExceptionHandling();

        InsuredPerson::creating(fn () => throw new RuntimeException('storage failure'));

        try {
            $this->reserve($this->payload());
            $this->fail('Expected exception was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('storage failure', $e->getMessage());
        }

        $this->assertDatabaseCount('contracts', 0);
        $this->assertDatabaseCount('insured_persons', 0);
        $this->assertDatabaseCount('idempotency_keys', 0);
        $this->assertSame(0, IdempotencyKey::query()->count());
    }

    public function test_it_validates_payload_before_reserving(): void
    {
        $this->actingAsUser();

        $this->reserve($this->payload(['insured_persons' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('insured_persons');

        $this->assertDatabaseCount('contracts', 0);
    }
}
