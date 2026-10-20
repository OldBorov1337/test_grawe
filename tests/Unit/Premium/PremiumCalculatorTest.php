<?php

namespace Tests\Unit\Premium;

use App\Exceptions\InvalidTariffRuleException;
use App\Models\Tariff;
use App\Services\ExchangeRate\ExchangeRate;
use App\Services\ExchangeRate\ExchangeRateProvider;
use App\Services\Premium\Data\InsuredPersonInput;
use App\Services\Premium\PremiumCalculator;
use Carbon\CarbonImmutable;
use Database\Seeders\TariffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public const RATE = 50.5301;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TariffSeeder::class);

        $this->app->instance(ExchangeRateProvider::class, new class implements ExchangeRateProvider
        {
            public function getRate(string $currency): ExchangeRate
            {
                return new ExchangeRate($currency, PremiumCalculatorTest::RATE, CarbonImmutable::parse('2026-10-21'));
            }
        });
    }

    private function calculate(array $persons)
    {
        return app(PremiumCalculator::class)->calculate(
            Tariff::query()->findOrFail(15),
            CarbonImmutable::parse('2026-11-01'),
            CarbonImmutable::parse('2027-10-31'),
            100000,
            $persons,
        );
    }

    public function test_it_calculates_example_from_the_task(): void
    {
        $result = $this->calculate([
            new InsuredPersonInput('Ivan', CarbonImmutable::parse('1995-05-10')),
            new InsuredPersonInput('Olena', CarbonImmutable::parse('1960-03-15')),
        ]);

        [$ivan, $olena] = $result->persons;

        $this->assertSame(31, $ivan->age);
        $this->assertSame(66, $olena->age);
        $this->assertSame(['age_coefficient' => 1.0, 'group_discount' => 0.9], $ivan->coefficients);
        $this->assertSame(['age_coefficient' => 1.8, 'group_discount' => 0.9], $olena->coefficients);
        $this->assertSame(135.0, $ivan->premiumBase);
        $this->assertSame(243.0, $olena->premiumBase);
        $this->assertSame(378.0, $result->totalBase);
        $this->assertSame(round(135 * self::RATE, 2), $ivan->premiumUah);
        $this->assertSame(round(243 * self::RATE, 2), $olena->premiumUah);
        $this->assertSame(round(round(135 * self::RATE, 2) + round(243 * self::RATE, 2), 2), $result->totalUah);
        $this->assertSame('EUR', $result->exchangeRate->currency);
        $this->assertSame(100000.0, $result->insuranceSum);
    }

    public function test_single_person_gets_no_discount(): void
    {
        $result = $this->calculate([
            new InsuredPersonInput('Ivan', CarbonImmutable::parse('1995-05-10')),
        ]);

        $this->assertSame(150.0, $result->persons[0]->premiumBase);
        $this->assertSame(1.0, $result->persons[0]->coefficients['group_discount']);
        $this->assertSame(150.0, $result->totalBase);
    }

    public function test_rules_are_loaded_from_database(): void
    {
        Tariff::query()->findOrFail(15)->rules()->where('type', 'group_discount')->update([
            'parameters' => json_encode(['min_persons' => 2, 'discount_percent' => 50]),
        ]);

        $result = $this->calculate([
            new InsuredPersonInput('A', CarbonImmutable::parse('1995-05-10')),
            new InsuredPersonInput('B', CarbonImmutable::parse('1995-05-10')),
        ]);

        $this->assertSame(75.0, $result->persons[0]->premiumBase);
        $this->assertSame(150.0, $result->totalBase);
    }

    public function test_unknown_rule_type_is_rejected(): void
    {
        Tariff::query()->findOrFail(15)->rules()->create(['type' => 'mystery', 'parameters' => [], 'priority' => 99]);

        $this->expectException(InvalidTariffRuleException::class);

        $this->calculate([new InsuredPersonInput('Ivan', CarbonImmutable::parse('1995-05-10'))]);
    }
}
