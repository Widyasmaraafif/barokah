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
            $table->string('courier')->nullable()->after('shipping_provider');
            $table->string('waybill_number')->nullable()->after('courier');
            $table->string('tracking_url', 500)->nullable()->after('waybill_number');
            $table->string('tracking_status', 50)->nullable()->after('tracking_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['courier', 'waybill_number', 'tracking_url', 'tracking_status']);
        });
    }
};
