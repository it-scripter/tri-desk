<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A person is addressed by first name in mails (placeholder {vorname}, H-41),
 * so the single name column is split. Birth date feeds the minimum age rules
 * (H-04), the mobile number the race day contact lists (H-02).
 *
 * No data is carried over from the name column: the only accounts that exist
 * at this point come from the seeders. The empty string default lets the
 * columns be added to a populated table without a data migration; a real
 * value is required by validation and by the factories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->default('')->after('id');
            $table->string('last_name')->default('')->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->date('birth_date')->nullable()->after('phone');
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->default('')->after('id');
            $table->dropColumn(['first_name', 'last_name', 'phone', 'birth_date']);
        });
    }
};
