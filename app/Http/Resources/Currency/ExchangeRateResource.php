<?php

namespace App\Http\Resources\Currency;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
            'synced_at' => $this->synced_at,
        ];
    }
}