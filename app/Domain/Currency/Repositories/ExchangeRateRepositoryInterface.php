<?php

namespace App\Domain\Currency\Repositories;

use Illuminate\Support\Collection;

interface ExchangeRateRepositoryInterface
{
    public function upsertForBase(array $rows, string $baseCode): void;

    public function getByCurrencyIds(array $currencyIds, string $baseCode): Collection;
}