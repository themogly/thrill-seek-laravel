<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Block-based campaigns store content in `blocks`, not `body`.
        Schema::table('newsletter_campaigns', function (Blueprint $table): void {
            $table->text('body')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_campaigns', function (Blueprint $table): void {
            $table->text('body')->nullable(false)->change();
        });
    }
};
