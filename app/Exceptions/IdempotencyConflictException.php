<?php

namespace App\Exceptions;

use RuntimeException;

class IdempotencyConflictException extends RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self("Idempotency key [{$key}] was already used with a different request payload.");
    }
}
