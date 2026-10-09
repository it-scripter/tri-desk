<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Role assignments with a context, as described in the specification chapter 8
 * ("Rollenzuweisung: Rolle, Kontext global/Ausgabe/OE").
 *
 * The scope columns are a nullable morph by intent, but stored with empty
 * defaults instead of NULL: MariaDB does not treat NULL values in a unique
 * index as equal, so a global assignment could otherwise be created twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('scope_type')->default('');
            $table->unsignedBigInteger('scope_id')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'role_id', 'scope_type', 'scope_id'], 'role_assignments_unique');
            $table->index(['scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_assignments');
    }
};
