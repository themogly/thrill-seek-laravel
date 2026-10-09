<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop two admin fields that fed nothing on the site (CMS field-usage gate,
 * prompt 012): products.duration (its only value, Tandem's "Approx. half a day at
 * the dropzone", is recorded in DECISIONS) and locations.image (empty on every
 * location). Files on disk are not touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'duration')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('duration');
            });
        }

        if (Schema::hasColumn('locations', 'image')) {
            Schema::table('locations', function (Blueprint $table): void {
                $table->dropColumn('image');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('duration')->nullable();
        });

        Schema::table('locations', function (Blueprint $table): void {
            $table->string('image')->nullable();
        });
    }
};
