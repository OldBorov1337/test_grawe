<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidTariffRuleException extends RuntimeException
{
    public static function unknownType(string $type): self
    {
        return new self("Tariff rule type [{$type}] is not registered.");
    }

    public static function invalidParameters(string $type, string $reason): self
    {
        return new self("Tariff rule [{$type}] has invalid parameters: {$reason}");
    }
}
