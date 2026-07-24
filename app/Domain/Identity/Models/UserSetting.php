<?php

namespace App\Domain\Identity\Models;

use App\Domain\Currency\Models\Currency;
use App\Domain\Setting\Models\Timezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'timezone_id',
        'timezone_source',
        'preferred_currency_id',
        'favorite_currency_ids',
        'locale',
        'theme',
        'date_format',
        'time_format',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'favorite_currency_ids' => 'array',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timezone(): BelongsTo
    {
        return $this->belongsTo(Timezone::class);
    }

    public function preferredCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'preferred_currency_id');
    }

    public function favoriteCurrencyIds(): array
    {
        return array_values(array_filter($this->favorite_currency_ids ?? []));
    }
}