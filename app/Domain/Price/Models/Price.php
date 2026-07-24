<?php

namespace App\Domain\Price\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Price extends Model
{
    protected $fillable = [
        'priceable_id',
        'priceable_type',
        'currency',
        'amount',
        'type',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }
}
