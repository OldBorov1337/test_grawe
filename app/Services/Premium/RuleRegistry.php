<?php

namespace App\Services\Premium;

use App\Exceptions\InvalidTariffRuleException;
use App\Models\TariffRule;

class RuleRegistry
{
    public function __construct(private readonly array $types)
    {
    }

    public function make(TariffRule $rule): PremiumRule
    {
        $class = $this->types[$rule->type] ?? null;

        if ($class === null || ! is_subclass_of($class, PremiumRule::class)) {
            throw InvalidTariffRuleException::unknownType($rule->type);
        }

        return new $class($rule->parameters ?? []);
    }

    public function has(string $type): bool
    {
        return isset($this->types[$type]);
    }
}
