<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Minimal orders table per spec §10.2. Statuses remain plain strings;
     * the Order/ payment status enums land in Task 7 (spec §14.2).
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_address', 500);
            $table->string('customer_state', 100);
            $table->string('customer_post_code', 20);
            $table->string('customer_phone', 30);
            $table->string('customer_email')->nullable();
            $table->string('currency_code', 3)->default('MYR');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status', 30)->default('pending_payment');
            $table->string('shipping_method', 20)->default('fixed');
            $table->string('shipping_provider')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
