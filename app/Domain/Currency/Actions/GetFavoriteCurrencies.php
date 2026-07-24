<?php

namespace App\Domain\Currency\Actions;

use App\Domain\Currency\Models\Currency;
use App\Domain\Currency\Models\ExchangeRate;
use App\Domain\Currency\Services\CurrencyQuoteService;
use App\Domain\Identity\Models\User;
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

        $systemBase = $this->quotes->systemBaseCode();

        $currencies = Currency::query()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get([
                'id',
                'code',
                'name',
                'symbol',
            ])
            ->keyBy('id');

        $rates = ExchangeRate::query()
            ->where('base', $systemBase)
            ->whereIn('currency_id', $ids)
            ->get([
                'currency_id',
                'rate',
                'synced_at',
            ])
            ->keyBy('currency_id');

        return collect($ids)
            ->map(function (int $id, int $index) use ($currencies, $rates) {
                $currency = $currencies->get($id);

                if (! $currency) {
                    return null;
                }

                $rate = $rates->get($id);

                $currency->setAttribute('favorite_position', $index + 1);
                $currency->setAttribute('exchange_rate_synced_at', $rate?->synced_at);

                return $currency;
            })
            ->filter()
            ->values();
    }
}