<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Test registrations: the orga team plays the public form through before
 * registration opens. They are stored and mailed like real ones, but never
 * hold a place, never count as duplicates and must stay out of every list
 * and export (scope Registration::real()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->boolean('is_test')->default(false)->after('entered_by_orga');
            $table->index('is_test');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['is_test']);
            $table->dropColumn('is_test');
        });
    }
};
