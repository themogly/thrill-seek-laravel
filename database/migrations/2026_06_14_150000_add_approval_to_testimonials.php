<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            // Customer-submitted reviews start unapproved and never show publicly
            // until an admin approves them. Existing (owner-curated) ones are live.
            $table->boolean('approved')->default(false)->after('featured');
            $table->foreignId('customer_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        DB::table('testimonials')->update(['approved' => true]);
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn('approved');
        });
    }
};
