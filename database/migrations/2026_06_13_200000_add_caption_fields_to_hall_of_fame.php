<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hall_of_fame_entries', function (Blueprint $table): void {
            // Optional richer caption detail; both backward-compatible.
            $table->date('achieved_on')->nullable()->after('milestone');
            $table->string('note')->nullable()->after('achieved_on');
        });
    }

    public function down(): void
    {
        Schema::table('hall_of_fame_entries', function (Blueprint $table): void {
            $table->dropColumn(['achieved_on', 'note']);
        });
    }
};
