<?php

namespace App\Domain\Currency\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimals',
        'is_active',
    ];

    public function exchangeRates()
    {
        return $this->hasMany(ExchangeRate::class);
    }
}
