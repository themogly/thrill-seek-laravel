<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();
            $table->dateTime('starts_at');
            $table->unsignedInteger('capacity');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('starts_at');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('availability_slot_id')
                ->nullable()
                ->after('scheduled_at')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('availability_slot_id');
        });

        Schema::dropIfExists('availability_slots');
    }
};
