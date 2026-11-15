<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Age group schemes (specification W-05), e.g. after DTU or DLV, or school
 * classes for the Schüler-Duathlon. A scheme is defined once and assigned to
 * races of any edition, so it is not tied to an edition itself.
 *
 * The age group is derived from the age reached in the year of the race
 * (birth year), as the DTU does it. diverse_scoring decides how entries with
 * the gender "d" are scored: in age groups of their own, or in the male or
 * female groups as chosen at registration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('age_group_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('diverse_scoring')->default('separate');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('age_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('age_group_scheme_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name')->nullable();
            // Null means the group is open to every gender, e.g. a kids class
            // scored together.
            $table->string('gender', 1)->nullable();
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->unsignedTinyInteger('max_age')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['age_group_scheme_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('age_groups');
        Schema::dropIfExists('age_group_schemes');
    }
};
