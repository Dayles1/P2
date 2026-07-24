<?php

namespace App\Http\Requests\Profile;

use App\Domain\Setting\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSettingRequest extends FormRequest
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
            'timezone_id' => [
                'nullable',
                'exists:timezones,id',
            ],

            'timezone_source' => [
                'nullable',
                'string',
                'max:50',
            ],

            'locale' => [
                'nullable',
                'string',
                'max:10',
            ],

            'preferred_currency_id' => [
                'nullable',
                'exists:currencies,id',
            ],

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

            'theme' => [
                'nullable',
                'in:light,dark,system',
            ],

            'date_format' => [
                'nullable',
                'string',
                'max:50',
            ],

            'time_format' => [
                'nullable',
                'string',
                'max:50',
            ],

            'meta' => [
                'nullable',
                'array',
            ],
        ];
    }
}