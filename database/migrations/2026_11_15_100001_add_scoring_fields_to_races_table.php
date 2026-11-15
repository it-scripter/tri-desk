<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scoring settings of a race (specification W-05):
 *
 * - the age group scheme the race is scored by
 * - whether the gender "d" may be entered; championships run without it
 * - the classifications a relay or team may be scored in (male, female,
 *   mixed, open). How a team is assigned to one of them follows from its
 *   members and is settled with the registration (package 2b).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->foreignId('age_group_scheme_id')
                ->nullable()
                ->after('max_age')
                ->constrained()
                ->nullOnDelete();
            $table->boolean('allows_diverse')->default(false)->after('age_group_scheme_id');
            $table->json('team_classifications')->nullable()->after('allows_diverse');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropConstrainedForeignId('age_group_scheme_id');
            $table->dropColumn(['allows_diverse', 'team_classifications']);
        });
    }
};
