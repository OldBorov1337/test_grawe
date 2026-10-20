<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    public const STATUS_RESERVED = 'reserved';

    protected $fillable = [
        'user_id',
        'tariff_id',
        'date_start',
        'date_end',
        'insurance_sum',
        'currency',
        'exchange_rate',
        'premium_base',
        'premium_uah',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_start' => 'date:Y-m-d',
            'date_end' => 'date:Y-m-d',
            'insurance_sum' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'premium_base' => 'decimal:2',
            'premium_uah' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    public function insuredPersons(): HasMany
    {
        return $this->hasMany(InsuredPerson::class);
    }
}
