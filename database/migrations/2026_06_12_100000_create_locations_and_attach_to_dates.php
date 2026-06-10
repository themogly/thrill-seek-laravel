<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Safe on a live database: creates locations, backfills both date
     * tables from existing data (course location strings become Location
     * rows; tandem slots default to the primary UK location), then makes
     * the new foreign keys required.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address_line')->nullable();
            $table->string('town')->nullable();
            $table->string('region')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country')->default('United Kingdom');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // The primary UK dropzone — inferred from the tandem page content —
        // is the backfill default for existing tandem availability.
        $defaultLocationId = DB::table('locations')->insertGetId([
            'name' => 'Devon',
            'slug' => 'devon',
            'region' => 'Devon',
            'country' => 'United Kingdom',
            'description' => 'Our home dropzone in the South West.',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('tandem_dates', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->index('location_id');
        });

        Schema::table('course_dates', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('product_id')->constrained()->restrictOnDelete();
            $table->index('location_id');
        });

        DB::table('tandem_dates')->whereNull('location_id')->update(['location_id' => $defaultLocationId]);

        // Every distinct course location string becomes a Location row.
        $courseLocations = DB::table('course_dates')->whereNotNull('location')->distinct()->pluck('location');

        foreach ($courseLocations as $name) {
            $slug = Str::slug($name);

            $locationId = DB::table('locations')->where('slug', $slug)->value('id')
                ?? DB::table('locations')->insertGetId([
                    'name' => $name,
                    'slug' => $slug,
                    'country' => str_contains($name, 'Spain') ? 'Spain' : 'United Kingdom',
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('course_dates')->where('location', $name)->update(['location_id' => $locationId]);
        }

        DB::table('course_dates')->whereNull('location_id')->update(['location_id' => $defaultLocationId]);

        // Backfill complete — the FKs become required and the legacy string
        // column goes away.
        Schema::table('tandem_dates', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable(false)->change();
        });

        Schema::table('course_dates', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable(false)->change();
        });

        Schema::table('course_dates', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }

    public function down(): void
    {
        Schema::table('course_dates', function (Blueprint $table) {
            $table->string('location')->nullable();
        });

        Schema::table('course_dates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::table('tandem_dates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::dropIfExists('locations');
    }
};
