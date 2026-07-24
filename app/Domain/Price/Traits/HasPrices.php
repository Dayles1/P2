<?php

namespace App\Domain\Price\Traits;

use App\Domain\Price\Models\Price;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasPrices
{
    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function activePrice(string $currency = 'UZS', string $type = 'regular')
    {
        return $this->prices()
            ->where('currency', $currency)
            ->where('type', $type)
            ->where('is_active', true)
            ->latest()
            ->first();
    }
}