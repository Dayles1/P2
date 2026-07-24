<?php

namespace App\Http\Requests\Currency;

use App\Domain\Setting\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class SyncExchangeRatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxFavorites = (int) (
            Setting::query()
                ->byKey('user.max_favorite_currency_count')
                ->value('value')
            ?? 10
        );
        return [
            'favorite_currency_ids' => [
                'nullable',
                'array',
                "max:{$maxFavorites}",
            ],

            'favorite_currency_ids.*' => [
                'integer',
                'distinct',
                'exists:currencies,id',
            ],
        ];
    }
}