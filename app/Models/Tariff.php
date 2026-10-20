<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tariff extends Model
{
    protected $fillable = [
        'name',
        'base_rate',
        'currency',
        'insurance_sum',
        'max_insured_persons',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_rate' => 'decimal:5',
            'insurance_sum' => 'decimal:2',
            'max_insured_persons' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(TariffRule::class)->orderBy('priority');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
