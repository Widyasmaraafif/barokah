<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Proof of manual payment (bank transfer / static QR).
     *
     * Buyers upload a receipt image after placing a manual order; admin
     * verifies it on the order detail page before marking paid.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('proof_path')->nullable()->after('callback_payload');
            $table->timestamp('proof_uploaded_at')->nullable()->after('proof_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['proof_path', 'proof_uploaded_at']);
        });
    }
};
