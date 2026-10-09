<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single starting class within an edition (specification W-02). The
 * discipline sequence is free per race, so transition zones, split times and
 * result lists can follow it.
 *
 * Price tiers, extra options and extra fields (W-03, W-11) belong to phase 2
 * and get their own tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('races', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edition_day_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('type')->default('individual');
            $table->json('discipline_sequence');
            $table->json('distances')->nullable();
            $table->time('starts_at')->nullable();
            $table->unsignedInteger('participant_limit')->nullable();
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->unsignedTinyInteger('max_age')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->unique(['edition_id', 'slug']);
            $table->index('edition_day_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('races');
    }
};
