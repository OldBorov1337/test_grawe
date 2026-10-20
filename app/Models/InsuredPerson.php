<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuredPerson extends Model
{
    protected $table = 'insured_persons';

    protected $fillable = [
        'contract_id',
        'name',
        'birth_date',
        'age',
        'age_coefficient',
        'discount_coefficient',
        'premium_base',
        'premium_uah',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
            'age' => 'integer',
            'age_coefficient' => 'decimal:3',
            'discount_coefficient' => 'decimal:3',
            'premium_base' => 'decimal:2',
            'premium_uah' => 'decimal:2',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
