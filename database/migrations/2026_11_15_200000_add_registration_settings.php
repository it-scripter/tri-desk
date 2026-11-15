<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settings the registration needs from races and editions:
 *
 * - team size of relays and teams (W-12); null means no bound
 * - the minimum per gender a mixed team has to reach (W-18), by default one
 *   male and one female member
 * - the payment window of an edition (W-22): an unpaid place is released
 *   after this many hours
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->unsignedTinyInteger('team_size_min')->nullable()->after('type');
            $table->unsignedTinyInteger('team_size_max')->nullable()->after('team_size_min');
            $table->unsignedTinyInteger('mixed_min_male')->default(1)->after('team_classifications');
            $table->unsignedTinyInteger('mixed_min_female')->default(1)->after('mixed_min_male');
        });

        Schema::table('editions', function (Blueprint $table) {
            $table->unsignedSmallInteger('payment_window_hours')->default(48)->after('transfer_deadline_at');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn(['team_size_min', 'team_size_max', 'mixed_min_male', 'mixed_min_female']);
        });

        Schema::table('editions', function (Blueprint $table) {
            $table->dropColumn('payment_window_hours');
        });
    }
};
