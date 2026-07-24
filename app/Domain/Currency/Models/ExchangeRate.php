<?php

namespace App\Domain\Currency\Models;


use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'code',
        'base',
        'rate',
        'source',
        'synced_at',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'synced_at' => 'datetime',
    ];
}