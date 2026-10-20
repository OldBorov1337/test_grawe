<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffRule extends Model
{
    protected $fillable = [
        'tariff_id',
        'type',
        'parameters',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'priority' => 'integer',
        ];
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }
}
