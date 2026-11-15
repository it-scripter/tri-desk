<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additional options of an edition (specification W-03): shirt, pasta party,
 * timing chip rental. An option is offered to every race of the edition
 * unless it is restricted to some of them through race_option_race.
 *
 * Variants carry choices such as shirt sizes, each with an optional quota.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('race_option_race', function (Blueprint $table) {
            $table->foreignId('race_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();

            $table->primary(['race_option_id', 'race_id']);
        });

        Schema::create('race_option_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_option_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_option_variants');
        Schema::dropIfExists('race_option_race');
        Schema::dropIfExists('race_options');
    }
};
