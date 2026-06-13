<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table): void {
            $table->string('name')->nullable()->after('email');
            $table->string('status')->default('pending')->index()->after('name');
            $table->string('source')->nullable()->after('status');
            $table->timestamp('consented_at')->nullable()->after('source');
            $table->timestamp('confirmed_at')->nullable()->after('consented_at');
            $table->timestamp('unsubscribed_at')->nullable()->after('confirmed_at');
        });

        // Existing rows pre-date double opt-in: treat them as already consented
        // and confirmed so the upgrade never silently drops a real subscriber.
        DB::table('newsletter_subscribers')->update([
            'status' => 'confirmed',
            'consented_at' => DB::raw('created_at'),
            'confirmed_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table): void {
            $table->dropColumn(['name', 'status', 'source', 'consented_at', 'confirmed_at', 'unsubscribed_at']);
        });
    }
};
