<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->id();

            $table->morphs('priceable');

            $table->string('currency', 5)->default('UZS');
            $table->decimal('amount', 20, 2);                
            $table->string('type')->nullable();              
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['priceable_id', 'priceable_type', 'currency', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};