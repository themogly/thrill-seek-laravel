<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_campaigns', function (Blueprint $table): void {
            $table->string('name')->nullable()->after('id');
            $table->string('preheader')->nullable()->after('subject');
            $table->string('status')->default('draft')->index()->after('preheader');
            // Ordered content blocks (Filament Builder JSON). The legacy `body`
            // column stays for already-sent Round 7 campaigns.
            $table->json('blocks')->nullable()->after('body');
            $table->longText('rendered_html')->nullable()->after('blocks');
            $table->timestamp('scheduled_at')->nullable()->after('sent_at');
        });

        // Existing sent campaigns are 'sent'; the column default covers new drafts.
        DB::table('newsletter_campaigns')
            ->whereNotNull('sent_at')
            ->update(['status' => 'sent']);
    }

    public function down(): void
    {
        Schema::table('newsletter_campaigns', function (Blueprint $table): void {
            $table->dropColumn(['name', 'preheader', 'status', 'blocks', 'rendered_html', 'scheduled_at']);
        });
    }
};
