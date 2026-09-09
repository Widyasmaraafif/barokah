<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table): void {
            $table->renameColumn('state', 'to_state');
            $table->renameColumn('city', 'to_city');
            $table->string('from_state', 100)->nullable()->after('id');
            $table->string('from_city', 100)->nullable()->after('from_state');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table): void {
            $table->dropColumn(['from_state', 'from_city']);
            $table->renameColumn('to_state', 'state');
            $table->renameColumn('to_city', 'city');
        });
    }
};
