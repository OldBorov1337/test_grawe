<?php

namespace Tests\Unit\Premium;

use App\Exceptions\InvalidTariffRuleException;
use App\Models\Tariff;
use App\Services\Premium\Data\CalculationContext;
use App\Services\Premium\Data\InsuredPersonInput;
use App\Services\Premium\Rules\AgeCoefficientRule;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AgeCoefficientRuleTest extends TestCase
{
    private function rule(): AgeCoefficientRule
    {
        return new AgeCoefficientRule([
            'ranges' => [
                ['min_age' => 0, 'max_age' => 60, 'coefficient' => 1.0],
                ['min_age' => 61, 'max_age' => null, 'coefficient' => 1.8],
            ],
        ]);
    }

    private function context(string $dateStart = '2026-11-01'): CalculationContext
    {
        return new CalculationContext(
            new Tariff(['currency' => 'EUR']),
            CarbonImmutable::parse($dateStart),
            CarbonImmutable::parse($dateStart)->addYear()->subDay(),
            1,
        );
    }

    public static function ages(): array
    {
        return [
            'newborn on start date' => ['2026-11-01', 1.0],
            'thirty one' => ['1995-05-10', 1.0],
            'exactly sixty' => ['1966-11-01', 1.0],
            'day before sixty' => ['1966-11-02', 1.0],
            'exactly sixty one' => ['1965-11-01', 1.8],
            'sixty six' => ['1960-03-15', 1.8],
        ];
    }

    #[DataProvider('ages')]
    public function test_it_returns_coefficient_by_full_years_at_date_start(string $birthDate, float $expected): void
    {
        $person = new InsuredPersonInput('Test', CarbonImmutable::parse($birthDate));

        $this->assertSame($expected, $this->rule()->coefficient($person, $this->context()));
    }

    public function test_it_requires_ranges(): void
    {
        $this->expectException(InvalidTariffRuleException::class);

        new AgeCoefficientRule([]);
    }

    public function test_it_fails_when_no_range_matches(): void
    {
        $rule = new AgeCoefficientRule(['ranges' => [['min_age' => 18, 'max_age' => 60, 'coefficient' => 1.0]]]);
        $person = new InsuredPersonInput('Kid', CarbonImmutable::parse('2020-01-01'));

        $this->expectException(InvalidTariffRuleException::class);

        $rule->coefficient($person, $this->context());
    }
}
