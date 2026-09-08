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
        Schema::table('users', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('state');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_city', 100)->nullable()->after('customer_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('customer_city');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('city');
        });
    }
};
