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

        $toPreferred = $quotes->quote($preferredCode, $this->code);
        $toSystem = $quotes->quote($systemBaseCode, $this->code);

        return [
            'id' => $this->id,
            'position' => $this->favorite_position ?? null,

            'currency' => [
                'id' => $this->id,
                'code' => $this->code,
                'name' => $this->name,
                'symbol' => $this->symbol,
            ],

            'base' => [
                'system' => $systemBaseCode,
                'preferred' => $preferredCode,
            ],

            'value' => [
                '1_' . strtolower($preferredCode) . '_in_' . strtolower($this->code) => $toPreferred['one_from_in_to'],
                '1_' . strtolower($this->code) . '_in_' . strtolower($preferredCode) => $toPreferred['one_to_in_from'],
            ],

            'comparison' => [
                'preferred_to_currency' => [
                    'from' => $toPreferred['from_code'],
                    'to' => $toPreferred['to_code'],
                    'rate' => $toPreferred['one_from_in_to'],
                    'inverse_rate' => $toPreferred['one_to_in_from'],
                ],
                'system_to_currency' => [
                    'from' => $toSystem['from_code'],
                    'to' => $toSystem['to_code'],
                    'rate' => $toSystem['one_from_in_to'],
                    'inverse_rate' => $toSystem['one_to_in_from'],
                ],
            ],

            'synced_at' => $formatter->format($this->synced_at ?? now(), $user),
        ];
    }
}