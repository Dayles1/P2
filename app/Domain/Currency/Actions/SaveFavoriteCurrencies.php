<?php

namespace App\Domain\Currency\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Identity\Models\UserSetting;

class SaveFavoriteCurrencies
{
    public function handle(
        User $user,
        array $favoriteCurrencyIds
    ): UserSetting {
        $settings = $user->settings()->firstOrCreate([]);

        $settings->update([
            'favorite_currency_ids' => array_values($favoriteCurrencyIds),
        ]);

        return $settings->fresh([
            'preferredCurrency',
            'timezone',
        ]);
    }
}