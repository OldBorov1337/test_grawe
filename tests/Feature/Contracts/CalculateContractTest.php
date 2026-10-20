<?php

namespace Tests\Feature\Contracts;

use PHPUnit\Framework\Attributes\DataProvider;

class CalculateContractTest extends ContractTestCase
{
    public function test_it_requires_authentication(): void
    {
        $this->postJson('/api/contracts/calculate', $this->payload())
            ->assertUnauthorized();
    }

    public function test_it_calculates_premium_for_the_task_example(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/contracts/calculate', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.tariff.id', 15)
            ->assertJsonPath('data.exchange_rate.rate', self::RATE)
            ->assertJsonPath('data.insured_persons.0.age', 31)
            ->assertJsonPath('data.insured_persons.0.premium', 135)
            ->assertJsonPath('data.insured_persons.1.age', 66)
            ->assertJsonPath('data.insured_persons.1.premium', 243)
            ->assertJsonPath('data.total_premium', 378)
            ->assertJsonPath('data.total_premium_uah', round(round(135 * self::RATE, 2) + round(243 * self::RATE, 2), 2));
    }

    public static function invalidPayloads(): array
    {
        return [
            'missing tariff' => [['tariff_id' => null], 'tariff_id'],
            'unknown tariff' => [['tariff_id' => 999], 'tariff_id'],
            'bad date format' => [['date_start' => '01.11.2026'], 'date_start'],
            'impossible date' => [['date_start' => '2026-02-30'], 'date_start'],
            'end before start' => [['date_end' => '2026-10-31'], 'date_end'],
            'zero insurance sum' => [['insurance_sum' => 0], 'insurance_sum'],
            'negative insurance sum' => [['insurance_sum' => -1], 'insurance_sum'],
            'no persons' => [['insured_persons' => []], 'insured_persons'],
            'person without name' => [['insured_persons' => [['name' => '', 'birth_date' => '1990-01-01']]], 'insured_persons.0.name'],
            'birth date after start' => [['insured_persons' => [['name' => 'Future', 'birth_date' => '2026-11-02']]], 'insured_persons.0.birth_date'],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_it_validates_input(array $overrides, string $errorKey): void
    {
        $this->actingAsUser();

        $this->postJson('/api/contracts/calculate', $this->payload($overrides))
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors($errorKey);
    }

    public function test_it_limits_persons_by_tariff(): void
    {
        $this->actingAsUser();

        $persons = array_map(fn (int $i) => ['name' => "P{$i}", 'birth_date' => '1990-01-01'], range(1, 11));

        $this->postJson('/api/contracts/calculate', $this->payload(['insured_persons' => $persons]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('insured_persons');
    }

    public function test_it_allows_ten_persons(): void
    {
        $this->actingAsUser();

        $persons = array_map(fn (int $i) => ['name' => "P{$i}", 'birth_date' => '1990-01-01'], range(1, 10));

        $this->postJson('/api/contracts/calculate', $this->payload(['insured_persons' => $persons]))
            ->assertOk()
            ->assertJsonCount(10, 'data.insured_persons');
    }

    public function test_it_returns_503_when_exchange_rate_is_unavailable(): void
    {
        $this->actingAsUser();
        $this->fakeExchangeRateUnavailable();

        $this->postJson('/api/contracts/calculate', $this->payload())
            ->assertStatus(503)
            ->assertJsonStructure(['message', 'errors' => ['exchange_rate']]);
    }
}
