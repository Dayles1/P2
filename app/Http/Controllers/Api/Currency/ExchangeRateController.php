<?php

namespace App\Http\Controllers\Api\Currency;

use App\Domain\Currency\Actions\GetExchangeRates;
use App\Http\Controllers\Controller;
use App\Http\Resources\Currency\ExchangeRateResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExchangeRateController extends Controller
{
    public function index(GetExchangeRates $action): AnonymousResourceCollection
    {
        return ExchangeRateResource::collection(
            $action->handle()
        );
    }
}