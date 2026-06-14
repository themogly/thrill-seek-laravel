<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiry_messages', function (Blueprint $table): void {
            // Inbound (customer) replies threaded in via the webhook.
            $table->string('sender_email')->nullable()->after('body');
            $table->timestamp('received_at')->nullable()->after('sender_email');
            // Full original (quoted history + signature kept) for safety; `body`
            // holds the cleaned reply.
            $table->longText('raw_body')->nullable()->after('received_at');
            $table->json('attachments')->nullable()->after('raw_body');
            // Resend email id — dedupe key so a re-delivered webhook never threads twice.
            $table->string('external_id')->nullable()->unique()->after('attachments');
        });

        Schema::table('enquiries', function (Blueprint $table): void {
            $table->timestamp('last_customer_message_at')->nullable()->after('read_at');
        });

        // Inbound mail that couldn't be routed to an enquiry — kept for manual
        // review rather than dropped.
        Schema::create('unmatched_inbound_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('external_id')->nullable()->unique();
            $table->string('from_email')->nullable();
            $table->string('to_email')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unmatched_inbound_messages');

        Schema::table('enquiries', function (Blueprint $table): void {
            $table->dropColumn('last_customer_message_at');
        });

        Schema::table('enquiry_messages', function (Blueprint $table): void {
            $table->dropUnique(['external_id']);
            $table->dropColumn(['sender_email', 'received_at', 'raw_body', 'attachments', 'external_id']);
        });
    }
};
