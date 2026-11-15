<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additional fields per race (specification W-11) and the consents of the
 * registration form (W-10).
 *
 * A field asks either once per registration (e.g. emergency contact) or for
 * every person (e.g. club, licence number). The answers are kept as JSON on
 * the registration or the participant, keyed by the field key, so a field can
 * be added to a race without a migration.
 *
 * Consents are stored with time and text version, like the guardian consent.
 * hide_name_in_lists is the objection to the name being shown in public start
 * and result lists - except for places 1 to 3 (specification 4.4). It holds
 * for the whole registration, so for a relay for all members alike.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('type')->default('text');
            $table->string('scope')->default('participant');
            $table->boolean('is_required')->default(false);
            $table->json('choices')->nullable();
            $table->string('help')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['race_id', 'key']);
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->json('extra')->nullable()->after('team_classification');
            $table->boolean('hide_name_in_lists')->default(false)->after('extra');
            $table->dateTime('terms_accepted_at')->nullable()->after('hide_name_in_lists');
            $table->string('terms_version')->nullable()->after('terms_accepted_at');
            $table->dateTime('privacy_acknowledged_at')->nullable()->after('terms_version');
            $table->string('privacy_version')->nullable()->after('privacy_acknowledged_at');
        });

        Schema::table('registration_participants', function (Blueprint $table) {
            $table->json('extra')->nullable()->after('age_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('registration_participants', function (Blueprint $table) {
            $table->dropColumn('extra');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn([
                'extra',
                'hide_name_in_lists',
                'terms_accepted_at',
                'terms_version',
                'privacy_acknowledged_at',
                'privacy_version',
            ]);
        });

        Schema::dropIfExists('race_fields');
    }
};
