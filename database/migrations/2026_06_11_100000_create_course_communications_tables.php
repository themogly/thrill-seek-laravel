<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });

        Schema::create('course_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_date_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->json('recipients');
            $table->string('source')->default('manual');
            $table->timestamps();
        });

        Schema::create('course_message_document', function (Blueprint $table) {
            $table->foreignId('course_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->primary(['course_message_id', 'document_id']);
        });

        Schema::create('course_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_date_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('days_before');
            $table->string('subject');
            $table->text('body');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_reminders');
        Schema::dropIfExists('course_message_document');
        Schema::dropIfExists('course_messages');
        Schema::dropIfExists('documents');
    }
};
