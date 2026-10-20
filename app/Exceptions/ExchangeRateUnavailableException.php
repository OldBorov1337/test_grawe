<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class ExchangeRateUnavailableException extends RuntimeException
{
    public static function forCurrency(string $currency, ?Throwable $previous = null): self
    {
        return new self("Exchange rate for {$currency} is currently unavailable.", 0, $previous);
    }
}
