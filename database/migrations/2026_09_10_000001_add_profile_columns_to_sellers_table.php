<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store profile fields for admin detail/edit (photo, contacts,
     * location, bank account, state/city).
     */
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->string('profile_photo_path', 500)->nullable()->after('description');
            // Phone/whatsapp formats are TBC (spec §24 item 5); only length is enforced.
            $table->string('phone', 30)->nullable()->after('profile_photo_path');
            $table->string('whatsapp', 30)->nullable()->after('phone');
            $table->string('store_location', 500)->nullable()->after('whatsapp');
            $table->string('bank_account', 255)->nullable()->after('store_location');
            $table->string('state', 100)->nullable()->after('bank_account');
            $table->string('city', 100)->nullable()->after('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo_path',
                'phone',
                'whatsapp',
                'store_location',
                'bank_account',
                'state',
                'city',
            ]);
        });
    }
};
