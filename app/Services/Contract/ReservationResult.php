<?php

namespace App\Services\Contract;

use App\Models\Contract;

final readonly class ReservationResult
{
    public function __construct(
        public Contract $contract,
        public bool $created,
    ) {
    }
}
