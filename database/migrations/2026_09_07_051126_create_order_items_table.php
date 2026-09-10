<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
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

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
