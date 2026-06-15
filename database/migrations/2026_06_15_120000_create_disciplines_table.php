<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        // An instructor can hold MANY disciplines (e.g. AFF + Coaching), and a
        // discipline is taught by many instructors — a plain many-to-many pivot
        // so each instructor is shown once with all their tags, never duplicated.
        Schema::create('discipline_instructor', function (Blueprint $table) {
            $table->foreignId('instructor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discipline_id')->constrained()->cascadeOnDelete();
            $table->primary(['instructor_id', 'discipline_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discipline_instructor');
        Schema::dropIfExists('disciplines');
    }
};
