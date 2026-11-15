<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cancellation rules of an edition (specification W-04) as steps: a
 * cancellation up to valid_until refunds refund_percent of the amount paid,
 * less a fee. The earliest step still open applies; after the last step
 * nothing is refunded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancellation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->dateTime('valid_until');
            $table->unsignedTinyInteger('refund_percent');
            $table->unsignedInteger('fee_cents')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_rules');
    }
};
