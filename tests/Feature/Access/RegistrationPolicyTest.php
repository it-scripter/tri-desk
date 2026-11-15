<?php

use App\Domain\Access\Enums\RoleName;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\Registration;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Registrations follow the permissions of their edition (specification 3):
 * the orga team sees and cancels the own ones, nobody deletes them.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->ownEdition = Edition::factory()->create(['year' => 2027, 'slug' => '2027']);
    $this->otherEdition = Edition::factory()->create(['year' => 2026, 'slug' => '2026']);

    $this->own = Registration::factory()->for(Race::factory()->for($this->ownEdition))->create();
    $this->other = Registration::factory()->for(Race::factory()->for($this->otherEdition))->create();

    $this->orga = User::factory()->create();
    $this->orga->assignRoleFor(RoleName::Orga, $this->ownEdition);
});

it('lets the orga team see and cancel registrations of its own edition only', function () {
    expect($this->orga->can('view', $this->own))->toBeTrue()
        ->and($this->orga->can('cancel', $this->own))->toBeTrue()
        ->and($this->orga->can('view', $this->other))->toBeFalse()
        ->and($this->orga->can('cancel', $this->other))->toBeFalse();
});

it('keeps registrations of a closed edition read only', function () {
    $this->ownEdition->update(['status' => 'archived']);
    $registration = $this->own->fresh();

    expect($this->orga->can('view', $registration))->toBeTrue()
        ->and($this->orga->can('cancel', $registration))->toBeFalse();
});

it('never lets the orga team delete a registration', function () {
    expect($this->orga->can('delete', $this->own))->toBeFalse();
});

it('keeps registrations away from the editorial role', function () {
    $editor = User::factory()->create();
    $editor->assignRoleFor(RoleName::Redaktion, $this->ownEdition);

    expect($editor->can('view', $this->own))->toBeFalse()
        ->and($editor->can('viewAny', Registration::class))->toBeFalse();
});

it('lists only registrations the user may see', function () {
    expect(Registration::query()->visibleTo($this->orga)->pluck('id')->all())->toBe([$this->own->id]);
});
