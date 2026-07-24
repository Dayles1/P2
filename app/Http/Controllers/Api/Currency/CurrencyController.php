<?php

namespace App\Http\Controllers\Api\Currency;

use App\Domain\Currency\Actions\GetCurrencies;
use App\Http\Controllers\Controller;
use App\Http\Resources\Currency\CurrencyResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CurrencyController extends Controller
{
    public function index(GetCurrencies $action): AnonymousResourceCollection
    {
        return CurrencyResource::collection(
            $action->handle()
        );
    }
    // public function faco...(Request $request, CurrencyConverterService $converter): JsonResponse
    // {
    //     $user = $request->user();
    //     $settings = $user?->settings;

    //     $favoriteIds = array_values(array_unique($settings?->favoriteCurrencyIds() ?? []));
    //     $favoriteIds = array_slice($favoriteIds, 0, 10);

    //     $currencies = Currency::query()
    //         ->whereIn('id', $favoriteIds)
    //         ->where('is_active', true)
    //         ->get()
    //         ->sortBy(fn ($currency) => array_search($currency->id, $favoriteIds))
    //         ->values();

    //     $preferredCode = $converter->preferredCode($user);
    //     $baseCode = $converter->baseCode();

    //     $data = $currencies->map(function (Currency $currency) use ($converter, $preferredCode) {
    //         return [
    //             'id' => $currency->id,
    //             'code' => $currency->code,
    //             'name' => $currency->name,
    //             'symbol' => $currency->symbol,
    //             'pair' => $converter->pair($currency->code, $preferredCode),
    //         ];
    //     });

    //     return $this->success([
    //         'base_code' => $baseCode,
    //         'preferred_code' => $preferredCode,
    //         'favorite_currency_ids' => $favoriteIds,
    //         'currencies' => $data,
    //     ]);
    // }

}
