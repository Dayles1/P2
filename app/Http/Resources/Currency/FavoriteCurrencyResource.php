<?php

namespace App\Http\Resources\Currency;

use App\Domain\Currency\Services\CurrencyQuoteService;
use App\Domain\Setting\Services\UserDateFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteCurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $quotes = app(CurrencyQuoteService::class);
        $formatter = app(UserDateFormatter::class);

        $user = $request->user();

        $preferredCode = $quotes->preferredCode($user);
        $systemBaseCode = $quotes->systemBaseCode();

        $id = $this->id;
        $code = $this->code;
        $name = $this->name;
        $symbol = $this->symbol;
        $position = $this->favorite_position ?? null;

        $toPreferred = $quotes->quote($preferredCode, $code);
        $toSystem = $quotes->quote($systemBaseCode, $code);

        return [
            'id' => $id,
            'position' => $position,

            'currency' => [
                'id' => $id,
                'code' => $code,
                'name' => $name,
                'symbol' => $symbol,
            ],

            'base' => [
                'system' => $systemBaseCode,
                'preferred' => $preferredCode,
            ],

            'value' => [
                'rate_from_preferred' => $this->formatRate($toPreferred['one_from_in_to']),
                'rate_to_preferred' => $this->formatRate($toPreferred['one_to_in_from']),
                'rate_from_system' => $this->formatRate($toSystem['one_from_in_to']),
                'rate_to_system' => $this->formatRate($toSystem['one_to_in_from']),
            ],

            'comparison' => [
                'preferred_to_currency' => [
                    'from' => $toPreferred['from_code'],
                    'to' => $toPreferred['to_code'],
                    'rate' => $this->formatRate($toPreferred['one_from_in_to']),
                    'inverse_rate' => $this->formatRate($toPreferred['one_to_in_from']),
                ],
                'system_to_currency' => [
                    'from' => $toSystem['from_code'],
                    'to' => $toSystem['to_code'],
                    'rate' => $this->formatRate($toSystem['one_from_in_to']),
                    'inverse_rate' => $this->formatRate($toSystem['one_to_in_from']),
                ],
            ],

            'synced_at' => $formatter->format(
                $this->exchange_rate_synced_at ?? now(),
                $user
            ),
        ];
    }

    private function formatRate(float|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match (true) {
            $value >= 100 => number_format($value, 2, '.', ''),
            $value >= 1 => number_format($value, 4, '.', ''),
            default => number_format($value, 6, '.', ''),
        };
    }
}