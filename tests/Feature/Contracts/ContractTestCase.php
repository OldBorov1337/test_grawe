<?php

namespace Tests\Feature\Contracts;

use App\Models\User;
use Database\Seeders\TariffSeeder;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class ContractTestCase extends TestCase
{
    use RefreshDatabase;

    protected const RATE = 50.5301;

    private PromiseInterface $exchangeRateResponse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TariffSeeder::class);

        $this->fakeExchangeRate();

        Http::fake(['bank.gov.ua/*' => fn () => $this->exchangeRateResponse]);
    }

    protected function fakeExchangeRate(float $rate = self::RATE): void
    {
        $this->exchangeRateResponse = Http::response([
            ['cc' => 'EUR', 'rate' => $rate, 'exchangedate' => '21.10.2026'],
        ]);
    }

    protected function fakeExchangeRateUnavailable(): void
    {
        $this->exchangeRateResponse = Http::response([], 200);
    }

    protected function actingAsUser(?User $user = null): User
    {
        $user ??= User::factory()->create();

        Sanctum::actingAs($user);

        return $user;
    }

    protected function payload(array $overrides = []): array
    {
        return array_replace([
            'tariff_id' => 15,
            'date_start' => '2026-11-01',
            'date_end' => '2027-10-31',
            'insurance_sum' => 100000,
            'insured_persons' => [
                ['name' => 'Ivan', 'birth_date' => '1995-05-10'],
                ['name' => 'Olena', 'birth_date' => '1960-03-15'],
            ],
        ], $overrides);
    }
}
