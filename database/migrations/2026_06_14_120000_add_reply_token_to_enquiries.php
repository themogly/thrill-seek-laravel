<?php

use App\Models\Enquiry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            // Unguessable per-enquiry token used in the reply-to address
            // (enquiry+{token}@{inbound_domain}) to thread customer replies.
            $table->string('reply_token', 40)->nullable()->unique()->after('reference');
        });

        // Backfill existing enquiries so every one is replyable.
        Enquiry::query()->whereNull('reply_token')->each(function (Enquiry $enquiry): void {
            $enquiry->forceFill(['reply_token' => Str::random(32)])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            $table->dropUnique(['reply_token']);
            $table->dropColumn('reply_token');
        });
    }
};
