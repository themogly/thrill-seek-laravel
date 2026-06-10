<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Carries purchase intent (purchaser/recipient/message) for
            // payments that create a record only on webhook success, and the
            // voucher a checkout intends to redeem.
            $table->json('metadata')->nullable()->after('reference');
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('source')->default('admin')->after('status');
            $table->foreignId('payment_id')->nullable()->after('booking_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_id');
            $table->dropColumn('source');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
