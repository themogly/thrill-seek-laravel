<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('lead')->nullable();
            $table->longText('body');
            $table->string('featured_image')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('byline')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            // Optional link to an AFF course; the course's live availability is
            // surfaced on the article. Detaches (not deletes) if the course goes.
            $table->foreignId('course_date_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};
