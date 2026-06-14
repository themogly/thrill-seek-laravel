<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('location');
            $table->unsignedInteger('price_pence')->nullable();
            $table->unsignedInteger('deposit_pence')->nullable();
            $table->unsignedInteger('capacity');
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_on']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            // No ->after(): `tandem_date_id` is added by a later migration, so on
            // MySQL (which honours column position) this would reference a column
            // that doesn't exist yet. SQLite ignored ->after(), hiding the bug.
            $table->foreignId('course_date_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_date_id');
        });

        Schema::dropIfExists('course_dates');
    }
};
