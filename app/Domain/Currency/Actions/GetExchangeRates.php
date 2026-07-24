<?php

namespace App\Domain\Currency\Actions;

use App\Domain\Currency\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Collection;

class GetExchangeRates
{
    public function handle(): Collection
    {
        return ExchangeRate::query()
            ->with('currency')
            ->orderBy('currency_id')
            ->get();
    }
}