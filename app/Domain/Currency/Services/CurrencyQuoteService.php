<?php

namespace App\Domain\Currency\Services;

use App\Domain\Currency\Models\Currency;
use App\Domain\Currency\Models\ExchangeRate;
use App\Domain\Identity\Models\User;
use App\Domain\Setting\Models\Setting;
use Illuminate\Support\Facades\Cache;

class CurrencyQuoteService
{
    public function systemBaseCode(): string
    {
        return Cache::remember('currency:system_base_code', now()->addMinutes(30), function () {
            return strtoupper(
                Setting::query()
                    ->byKey('system.base_currency_code')
                    ->value('value') ?: 'USD'
            );
        });
    }

    public function preferredCode(?User $user = null): string
    {
        return strtoupper(
            $user?->settings?->preferredCurrency?->code
            ?? $this->systemBaseCode()
        );
    }

    public function ratesBySystemBase(): array
    {
        $base = $this->systemBaseCode();

        return Cache::remember("currency:rates:{$base}", now()->addMinutes(10), function () use ($base) {
            return ExchangeRate::query()
                ->with('currency:id,code')
                ->where('base', $base)
                ->get()
                ->mapWithKeys(function (ExchangeRate $rate) {
                    return [
                        strtoupper($rate->currency->code) => (float) $rate->rate,
                    ];
                })
                ->all();
        });
    }

    public function rateOf(string|Currency $currency): ?float
    {
        $code = $currency instanceof Currency
            ? strtoupper($currency->code)
            : strtoupper($currency);

        return $this->ratesBySystemBase()[$code] ?? null;
    }

    public function convert(string|Currency $from, string|Currency $to, float $amount = 1.0): ?float
    {
        $fromCode = $from instanceof Currency ? strtoupper($from->code) : strtoupper($from);
        $toCode = $to instanceof Currency ? strtoupper($to->code) : strtoupper($to);

        if ($fromCode === $toCode) {
            return $amount;
        }

        $fromRate = $this->rateOf($fromCode);
        $toRate = $this->rateOf($toCode);

        if ($fromRate === null || $toRate === null || $fromRate == 0.0) {
            return null;
        }

        // system base (USD) orqali conversion
        return ($amount / $fromRate) * $toRate;
    }

    public function quote(string|Currency $from, string|Currency $to): array
    {
        $forward = $this->convert($from, $to, 1.0);
        $reverse = $this->convert($to, $from, 1.0);

        return [
            'from_code' => $from instanceof Currency ? strtoupper($from->code) : strtoupper($from),
            'to_code' => $to instanceof Currency ? strtoupper($to->code) : strtoupper($to),
            'one_from_in_to' => $forward,
            'one_to_in_from' => $reverse,
        ];
    }

    public function favoriteCurrencyIds(?User $user = null): array
    {
        return array_slice(
            array_values(array_unique($user?->settings?->favoriteCurrencyIds() ?? [])),
            0,
            (int) (Setting::query()->byKey('user.max_favorite_currency_count')->value('value') ?? 10)
        );
    }
}