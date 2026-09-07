<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Minimal order_items table per spec §10.2. The `product_id` column has
     * no foreign key yet: the products table lands in Task 4 (spec §10.2).
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->string('product_name_snapshot');
            $table->string('product_slug_snapshot');
            $table->decimal('price_snapshot', 10, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->index('order_id');
            $table->index('seller_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
