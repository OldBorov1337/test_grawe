<?php

namespace Tests\Unit\Premium;

use App\Models\Tariff;
use App\Services\Premium\Data\CalculationContext;
use App\Services\Premium\Data\InsuredPersonInput;
use App\Services\Premium\Rules\GroupDiscountRule;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class GroupDiscountRuleTest extends TestCase
{
    private function context(int $personsCount): CalculationContext
    {
        return new CalculationContext(
            new Tariff(['currency' => 'EUR']),
            CarbonImmutable::parse('2026-11-01'),
            CarbonImmutable::parse('2027-10-31'),
            $personsCount,
        );
    }

    public function test_no_discount_below_minimum_persons(): void
    {
        $rule = new GroupDiscountRule(['min_persons' => 2, 'discount_percent' => 10]);
        $person = new InsuredPersonInput('Solo', CarbonImmutable::parse('1990-01-01'));

        $this->assertSame(1.0, $rule->coefficient($person, $this->context(1)));
    }

    public function test_discount_applies_from_minimum_persons(): void
    {
        $rule = new GroupDiscountRule(['min_persons' => 2, 'discount_percent' => 10]);
        $person = new InsuredPersonInput('Pair', CarbonImmutable::parse('1990-01-01'));

        $this->assertSame(0.9, $rule->coefficient($person, $this->context(2)));
        $this->assertSame(0.9, $rule->coefficient($person, $this->context(10)));
    }

    public function test_custom_percent_is_respected(): void
    {
        $rule = new GroupDiscountRule(['min_persons' => 3, 'discount_percent' => 25]);
        $person = new InsuredPersonInput('Group', CarbonImmutable::parse('1990-01-01'));

        $this->assertSame(1.0, $rule->coefficient($person, $this->context(2)));
        $this->assertSame(0.75, $rule->coefficient($person, $this->context(3)));
    }
}
