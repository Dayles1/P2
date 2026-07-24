<?php

namespace App\Http\Resources\Currency;

use App\Domain\Setting\Services\UserDateFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $formatter = app(UserDateFormatter::class);

        return [
            'currency' => [
                'id' => $this->currency->id,
                'code' => $this->currency->code,
                'name' => $this->currency->name,
                'symbol' => $this->currency->symbol,
            ],
            'base' => $this->base,
            'rate' => $this->rate,
            'source' => $this->source,
            'synced_at' => $formatter->format($this->synced_at, $request->user()),
        ];
    }
}