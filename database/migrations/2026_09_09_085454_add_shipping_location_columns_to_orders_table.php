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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_address', 500)->nullable()->after('customer_email');
            $table->string('shipping_state', 100)->nullable()->after('shipping_address');
            $table->string('shipping_city', 100)->nullable()->after('shipping_state');
            $table->string('shipping_post_code', 20)->nullable()->after('shipping_city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_address', 'shipping_state', 'shipping_city', 'shipping_post_code']);
        });
    }
};
