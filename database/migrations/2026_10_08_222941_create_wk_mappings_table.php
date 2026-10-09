<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maps a wk value of the legacy result database onto a race of this portal
 * (specification W-33, W-37). The wk column of the legacy table "Alle" holds
 * event, race and year as free text, e.g. "BerlinMan Jedermenschtri 1992".
 *
 * Several wk values may point at the same race: the MiniMan 2021 was recorded
 * per school class, the Jedermensch-Sprint 2008 under two spellings.
 *
 * expected_entries is the checked row count from docs/data/wk-zuordnung.csv and
 * serves as a target figure when the synchronisation runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wk_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('wk')->unique();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edition_day_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('sicher');
            $table->text('note')->nullable();
            $table->unsignedInteger('expected_entries')->nullable();
            $table->timestamps();

            $table->index('race_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wk_mappings');
    }
};
