<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_seller_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->string('shipping_provider')->nullable();
            $table->string('courier')->nullable();
            $table->string('waybill_number')->nullable();
            $table->string('tracking_url', 500)->nullable();
            $table->string('tracking_status', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'seller_id']);
        });

    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_seller_trackings');
    }
};
