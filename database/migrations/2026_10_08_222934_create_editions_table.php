<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One year of a competition (specification W-01). The edition is the central
 * reference point: races, org units and mail templates all hang off it.
 *
 * - number is the "Ausgabe-Nr." and only filled where the organiser counts
 *   them (Volkstriathlon 1 to 38); it is deliberately nullable.
 * - display_name carries the name the edition ran under, e.g. "BerlinMan" for
 *   BerlinWoMen editions up to 2024.
 * - There is no unique index on (competition_id, year): the Schueler-Duathlon
 *   had two dates in 2013, and a second edition within one year must stay
 *   possible. The slug is unique per competition instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('number')->nullable();
            $table->string('display_name')->nullable();
            $table->string('slug');
            $table->string('status')->default('planned');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->dateTime('transfer_deadline_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'slug']);
            $table->index(['competition_id', 'year']);
            $table->index('is_current');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editions');
    }
};
