<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-recipient sent-marker: claimed before queueing so a job retry or
        // an overlapping scheduler tick can never double-send to one subscriber.
        Schema::create('newsletter_campaign_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('newsletter_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('newsletter_subscriber_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['newsletter_campaign_id', 'newsletter_subscriber_id'], 'campaign_subscriber_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_campaign_recipients');
    }
};
