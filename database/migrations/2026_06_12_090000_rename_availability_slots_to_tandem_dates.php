<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tandem availability and AFF courses are different domain concepts;
     * the generically-named availability_slots table becomes tandem_dates.
     * Pure renames — safe on a live database, all data retained.
     */
    public function up(): void
    {
        Schema::rename('availability_slots', 'tandem_dates');

        Schema::table('bookings', function (Blueprint $table) {
            $table->renameColumn('availability_slot_id', 'tandem_date_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->renameColumn('tandem_date_id', 'availability_slot_id');
        });

        Schema::rename('tandem_dates', 'availability_slots');
    }
};
