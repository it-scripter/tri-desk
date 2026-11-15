<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registrations (specification 4.2). One row is one entry in a race: a single
 * person, or a whole relay or team (W-12) with its members in
 * registration_participants.
 *
 * Price tier and amount are frozen at the moment of registration, so a later
 * price change never alters what was agreed. reserved_until holds the place
 * while payment is outstanding (W-22).
 *
 * Participants have no account for now (specification 3): the entry is
 * managed through a magic link, of which only the hash is stored. user_id
 * keeps a later link to an account open.
 *
 * Only the personal data the specification asks for is kept (DSGVO): names,
 * birth date, gender and a contact e-mail - no address, no phone number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            // Restrict, not cascade: deleting a race must never silently take
            // its registrations along.
            $table->foreignId('race_id')->constrained()->restrictOnDelete();
            $table->foreignId('price_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->string('status');
            $table->dateTime('reserved_until')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('contact_first_name');
            $table->string('contact_last_name');
            $table->string('contact_email');
            $table->string('team_name')->nullable();
            $table->string('team_classification')->nullable();
            $table->char('token_hash', 64)->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('entered_by_orga')->default(false);
            $table->timestamps();

            $table->index(['race_id', 'status']);
            $table->index(['status', 'reserved_until']);
        });

        Schema::create('registration_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position')->default(0);
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date');
            $table->char('gender', 1);
            // The gender a "d" member is scored as, where the age group
            // scheme scores "as chosen" (W-05, W-18).
            $table->char('scored_as', 1)->nullable();
            $table->foreignId('age_group_id')->nullable()->constrained()->nullOnDelete();
            // Consent of a parent or guardian for minors (W-17).
            $table->string('guardian_name')->nullable();
            $table->dateTime('guardian_consented_at')->nullable();
            $table->string('guardian_consent_version')->nullable();
            $table->timestamps();

            // Duplicate check (W-16): name + birth date within a race.
            $table->index(['last_name', 'first_name', 'birth_date']);
        });

        Schema::create('registration_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('race_option_id')->constrained()->restrictOnDelete();
            $table->foreignId('race_option_variant_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('price_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_options');
        Schema::dropIfExists('registration_participants');
        Schema::dropIfExists('registrations');
    }
};
