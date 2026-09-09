<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('state', 100);
            $table->string('city', 100)->nullable();
            $table->decimal('rate', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['state', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
