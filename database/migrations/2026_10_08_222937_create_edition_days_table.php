<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An edition can span several days (specification 1.5); races, volunteer
 * shifts and setup work are tied to a single day.
 *
 * The date is nullable because it is not always known for historic editions:
 * the 25th Volkstriathlon (2013) is recorded as "Sa" and "So" without dates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edition_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->date('date')->nullable();
            $table->string('label')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['edition_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edition_days');
    }
};
