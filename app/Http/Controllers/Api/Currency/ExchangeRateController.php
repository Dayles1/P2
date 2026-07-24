<?php

namespace App\Http\Controllers\Api\Currency;

use App\Domain\Currency\Actions\GetExchangeRates;
use App\Domain\Currency\Actions\GetFavoriteCurrencies;
use App\Domain\Currency\Actions\SaveFavoriteCurrencies;
use App\Http\Controllers\Controller;
use App\Http\Requests\Currency\SyncExchangeRatesRequest;
use App\Http\Resources\Currency\ExchangeRateResource;
use App\Http\Resources\Currency\FavoriteCurrencyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExchangeRateController extends Controller
{
    public function index(GetExchangeRates $action): AnonymousResourceCollection
    {
        return ExchangeRateResource::collection(
            $action->handle()
        );
    }

    public function favorites(
        Request $request,
        GetFavoriteCurrencies $action
    ): JsonResponse {
        $currencies = $action->handle($request->user());

        return $this->success(
            data: FavoriteCurrencyResource::collection($currencies)
        );
    }

    public function saveFavorites(
        SyncExchangeRatesRequest $request,
        SaveFavoriteCurrencies $saveAction,
        GetFavoriteCurrencies $getAction
    ): JsonResponse {
        $saveAction->handle(
            user: $request->user(),
            favoriteCurrencyIds: $request->validated('favorite_currency_ids', [])
        );

        $currencies = $getAction->handle($request->user());

        return $this->success(
            data: FavoriteCurrencyResource::collection($currencies),
            message: __('messages.settings.updated')
        );
    }
}