<?php

use App\Domain\Competition\Models\CancellationRule;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Services\RefundCalculator;
use Illuminate\Support\Carbon;

/**
 * W-04: registration deadline, transfer deadline and cancellation rules per
 * edition. The rules are steps; the earliest step still open applies.
 */
beforeEach(function () {
    $this->edition = Edition::factory()->create([
        'year' => 2027,
        'slug' => '2027',
        'registration_closes_at' => '2027-06-20 23:59:59',
        'transfer_deadline_at' => '2027-06-13 23:59:59',
    ]);

    CancellationRule::factory()->for($this->edition)->create([
        'valid_until' => '2027-04-30 23:59:59',
        'refund_percent' => 100,
        'fee_cents' => 500,
    ]);
    CancellationRule::factory()->for($this->edition)->create([
        'valid_until' => '2027-05-31 23:59:59',
        'refund_percent' => 50,
        'fee_cents' => 0,
    ]);

    $this->calculator = new RefundCalculator;
});

it('keeps the deadlines on the edition', function () {
    expect($this->edition->registration_closes_at->format('d.m.Y H:i'))->toBe('20.06.2027 23:59')
        ->and($this->edition->transfer_deadline_at->format('d.m.Y H:i'))->toBe('13.06.2027 23:59');
});

it('refunds by the earliest step still open', function () {
    expect($this->calculator->refundFor($this->edition, 4500, Carbon::parse('2027-03-01')))->toBe(4000)
        ->and($this->calculator->refundFor($this->edition, 4500, Carbon::parse('2027-04-30 23:59:59')))->toBe(4000)
        ->and($this->calculator->refundFor($this->edition, 4500, Carbon::parse('2027-05-01')))->toBe(2250);
});

it('refunds nothing after the last step', function () {
    expect($this->calculator->ruleAt($this->edition, Carbon::parse('2027-06-01')))->toBeNull()
        ->and($this->calculator->refundFor($this->edition, 4500, Carbon::parse('2027-06-01')))->toBe(0);
});

it('never refunds a negative amount and rounds down to the cent', function () {
    $rule = new CancellationRule(['refund_percent' => 50, 'fee_cents' => 0]);
    $feeOnly = new CancellationRule(['refund_percent' => 10, 'fee_cents' => 1000]);

    expect($rule->refundFor(4501))->toBe(2250)
        ->and($feeOnly->refundFor(4500))->toBe(0);
});

it('lists the steps in order of their deadline', function () {
    expect($this->edition->cancellationRules->pluck('refund_percent')->all())->toBe([100, 50]);
});
