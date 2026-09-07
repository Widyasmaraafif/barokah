<?php

use App\Enums\SellerStatus;
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
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_active_as_seller')->default(false);
            $table->string('phone', 30)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('post_code', 20)->nullable();
        });

        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('store_name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default(SellerStatus::Active->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_admin',
                'is_active_as_seller',
                'phone',
                'address',
                'state',
                'post_code',
            ]);
        });
    }
};
