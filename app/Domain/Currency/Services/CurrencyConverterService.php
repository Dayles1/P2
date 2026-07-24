<?php

namespace App\Domain\Currency\Services;

use App\Domain\Currency\Models\Currency;
use App\Domain\Currency\Models\ExchangeRate;
use App\Domain\Identity\Models\User;
use App\Domain\Setting\Models\Setting;
use Illuminate\Support\Facades\Cache;

class CurrencyConverterService
{
    public function baseCode(): string
    {
        return Cache::remember('currency:base_code', now()->addMinutes(10), function () {
            return strtoupper(
                (string) Setting::query()
                    ->byKey('currency.base_code')
                    ->value('value') ?: 'USD'
            );
        });
    }

    public function preferredCode(?User $user = null): string
    {
        return strtoupper(
            $user?->settings?->preferredCurrency?->code
            ?? $this->baseCode()
        );
    }

    public function rateOf(string|Currency $currency): ?float
    {
        $code = $currency instanceof Currency
            ? strtoupper($currency->code)
            : strtoupper($currency);

        $rates = $this->rates();

        return $rates[$code] ?? null;
    }

    public function convert(float $amount, string|Currency $from, string|Currency $to): ?float
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

        return ($amount / $fromRate) * $toRate;
    }

    public function pair(string|Currency $from, string|Currency $to): array
    {
        $fromCode = $from instanceof Currency ? strtoupper($from->code) : strtoupper($from);
        $toCode = $to instanceof Currency ? strtoupper($to->code) : strtoupper($to);

        return [
            'from_code' => $fromCode,
            'to_code' => $toCode,
            'one_from_in_to' => $this->convert(1, $fromCode, $toCode),
            'one_to_in_from' => $this->convert(1, $toCode, $fromCode),
        ];
    }

    protected function rates(): array
    {
        $base = $this->baseCode();

        return Cache::remember("currency:rates:{$base}", now()->addMinutes(10), function () use ($base) {
            return ExchangeRate::query()
                ->with('currency:id,code')
                ->where('base', $base)
                ->get()
                ->mapWithKeys(function ($rate) {
                    return [
                        strtoupper($rate->currency->code) => (float) $rate->rate,
                    ];
                })
                ->all();
        });
    }
}