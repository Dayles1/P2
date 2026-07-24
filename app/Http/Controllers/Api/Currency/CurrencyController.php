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

}
