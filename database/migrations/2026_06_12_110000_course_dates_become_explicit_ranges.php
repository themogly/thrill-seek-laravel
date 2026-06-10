<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AFF courses are multi-day by definition (minimum 5 days). starts_on /
     * ends_on become start_date / end_date; any legacy row with no end date
     * or a shorter range is stretched to the 5-day minimum before end_date
     * becomes required. Safe on live data.
     */
    public function up(): void
    {
        Schema::table('course_dates', function (Blueprint $table) {
            $table->renameColumn('starts_on', 'start_date');
            $table->renameColumn('ends_on', 'end_date');
        });

        $rows = DB::table('course_dates')->select('id', 'start_date', 'end_date')->get();

        foreach ($rows as $row) {
            $minimumEnd = date('Y-m-d', strtotime($row->start_date.' +4 days'));

            if ($row->end_date === null || $row->end_date < $minimumEnd) {
                DB::table('course_dates')->where('id', $row->id)->update(['end_date' => $minimumEnd]);
            }
        }

        Schema::table('course_dates', function (Blueprint $table) {
            $table->date('end_date')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('course_dates', function (Blueprint $table) {
            $table->date('end_date')->nullable()->change();
        });

        Schema::table('course_dates', function (Blueprint $table) {
            $table->renameColumn('start_date', 'starts_on');
            $table->renameColumn('end_date', 'ends_on');
        });
    }
};
