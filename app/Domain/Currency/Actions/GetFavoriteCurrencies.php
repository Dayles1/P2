<?php

namespace App\Domain\Currency\Actions;

use App\Domain\Currency\Models\Currency;
use App\Domain\Identity\Models\User;
use App\Domain\Currency\Services\CurrencyQuoteService;
use Illuminate\Support\Collection;

class GetFavoriteCurrencies
{
    public function __construct(
        protected CurrencyQuoteService $quotes
    ) {}

    public function handle(User $user): Collection
    {
        $ids = $this->quotes->favoriteCurrencyIds($user);

        if (empty($ids)) {
            return collect();
        }

        $currencies = Currency::query()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(function (int $id, int $index) use ($currencies) {
                $currency = $currencies->get($id);

                if (! $currency) {
                    return null;
                }

                $currency->setAttribute('favorite_position', $index + 1);

                return $currency;
            })
            ->filter()
            ->values();
    }
}