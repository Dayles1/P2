<?php

namespace App\Console\Commands\Currency;

use App\Domain\Currency\Models\Currency;
use App\Domain\Currency\Services\CurrencySyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncExchangeRates extends Command
{
    protected $signature = 'currency:sync';
    protected $description = 'Sync currency rates from API and update local database';

    public function handle(CurrencySyncService $service): int
    {
        try {
            $count = $service->sync();

            $this->info("Currency sync tugadi. Jami: {$count}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}