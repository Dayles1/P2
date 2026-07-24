<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->string('base', 5)->default('USD');
            $table->decimal('rate', 20, 6);
            $table->string('source')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['currency_id', 'base']);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
