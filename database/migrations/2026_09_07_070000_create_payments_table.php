<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PayNet payment intent per spec §10.2/§15. One row per order; provider
     * payloads stay server-only and are never exposed via API resources.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('payment_gateway', 30)->default('paynet');
            $table->string('payment_method', 20);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('MYR');
            $table->string('status', 30)->default('pending');
            $table->string('transaction_id')->nullable()->unique();
            $table->json('payload')->nullable();
            $table->json('callback_payload')->nullable();
            $table->string('proof_path')->nullable();
            $table->timestamp('proof_uploaded_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
