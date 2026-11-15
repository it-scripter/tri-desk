<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Price tiers by date (specification W-03): early bird, normal, late entry.
 * The amount is the price of one entry - for a relay or team that is the
 * whole team, not each member.
 *
 * A tier may reach past the registration deadline. orga_only marks a tier the
 * public form never offers, e.g. late entry on race day through the orga team.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('amount_cents');
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->boolean('orga_only')->default(false);
            $table->timestamps();

            $table->index(['race_id', 'valid_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_tiers');
    }
};
