<?php

namespace App\Domain\Currency\Actions;

use App\Domain\Currency\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

class GetCurrencies
{
    public function handle(): Collection
    {
        return Currency::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }
}