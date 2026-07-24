<?php

namespace App\Domain\Currency\Services;

use App\Domain\Currency\Models\Currency;
use App\Domain\Currency\Models\ExchangeRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CurrencySyncService
{
    private const ENDPOINT = 'https://open.er-api.com/v6/latest/USD';

    private const CURRENCY_NAMES = [
        'USD' => 'US Dollar',
        'UZS' => 'Uzbek Soum',
        'EUR' => 'Euro',
        'RUB' => 'Russian Ruble',
        'TRY' => 'Turkish Lira',
        'CNY' => 'Chinese Yuan',
        'KZT' => 'Kazakh Tenge',
        'GBP' => 'British Pound',
        'JPY' => 'Japanese Yen',
        'KRW' => 'South Korean Won',
    ];

    private const CURRENCY_SYMBOLS = [
        'USD' => '$',
        'UZS' => "so'm",
        'EUR' => '€',
        'RUB' => '₽',
        'TRY' => '₺',
        'CNY' => '¥',
        'GBP' => '£',
        'JPY' => '¥',
        'KRW' => '₩',
    ];

    public function sync(): int
    {
        try {
            $response = Http::timeout(20)
                ->retry(3, 500)
                ->acceptJson()
                ->get(self::ENDPOINT);

            if (! $response->ok()) {
                throw new \RuntimeException("API xatosi: HTTP {$response->status()}");
            }

            $payload = $response->json();

            if (($payload['result'] ?? null) !== 'success' || empty($payload['rates']) || ! is_array($payload['rates'])) {
                throw new \RuntimeException('API dan yaroqli kurs ma\'lumoti kelmadi.');
            }

            $base = strtoupper($payload['base_code'] ?? 'USD');
            $now = now();
            $count = 0;

            DB::transaction(function () use ($payload, $base, $now, &$count) {
                foreach ($payload['rates'] as $code => $rate) {
                    if (! is_string($code) || ! is_numeric($rate)) {
                        continue;
                    }

                    $code = strtoupper($code);

                    $currency = Currency::updateOrCreate(
                        ['code' => $code],
                        [
                            'name' => self::CURRENCY_NAMES[$code] ?? $code,
                            'symbol' => self::CURRENCY_SYMBOLS[$code] ?? null,
                            'decimals' => in_array($code, ['JPY', 'KRW', 'VND'], true) ? 0 : 2,
                            'is_active' => true,
                        ]
                    );

                    ExchangeRate::updateOrCreate(
                        [
                            'currency_id' => $currency->id,
                            'base' => $base,
                        ],
                        [
                            'rate' => (float) $rate,
                            'source' => 'exchangerate-api',
                            'synced_at' => $now,
                        ]
                    );

                    $count++;
                }
            });

            return $count;
        } catch (Throwable $e) {
            Log::error('Currency sync failed', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}