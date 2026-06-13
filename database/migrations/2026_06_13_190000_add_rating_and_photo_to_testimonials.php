<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            // 1–5 star rating, optional.
            $table->unsignedTinyInteger('rating')->nullable()->after('role');
            // A large action/jump shot, distinct from the small avatar headshot.
            $table->string('photo')->nullable()->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            $table->dropColumn(['rating', 'photo']);
        });
    }
};
