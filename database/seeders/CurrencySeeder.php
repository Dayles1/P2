<?php

namespace Database\Seeders;

use App\Domain\Currency\Models\Currency;
use App\Domain\Currency\Services\CurrencySyncService;
use Illuminate\Database\Seeder;
use Throwable;

class CurrencySeeder extends Seeder
{
    public function run(CurrencySyncService $service): void
    {
        if (Currency::query()->exists()) {
            return;
        }

        try {
            $service->sync();
        } catch (Throwable $e) {
            $this->command?->error('CurrencySeeder xatosi: ' . $e->getMessage());
        }
    }
}