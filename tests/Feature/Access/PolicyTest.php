<?php

use App\Domain\Access\Enums\RoleName;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\EditionDay;
use App\Domain\Competition\Models\Race;
use App\Models\User;
use App\Policies\UserPolicy;
use Database\Seeders\RoleSeeder;

/**
 * Permissions are checked server side through policies, never by hiding menu
 * entries (specification 3, CLAUDE.md). The edition is the scope an orga team
 * is bound to.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->competition = Competition::factory()->create(['name' => 'Volkstriathlon', 'slug' => 'volkstriathlon']);
    $this->ownEdition = Edition::factory()->for($this->competition)->create(['year' => 2027, 'slug' => '2027']);
    $this->otherEdition = Edition::factory()->for($this->competition)->create(['year' => 2026, 'slug' => '2026']);

    $this->ownRace = Race::factory()->for($this->ownEdition)->create(['name' => 'Eigener Wettbewerb']);
    $this->otherRace = Race::factory()->for($this->otherEdition)->create(['name' => 'Fremder Wettbewerb']);

    $this->orga = User::factory()->create();
    $this->orga->assignRoleFor(RoleName::Orga, $this->ownEdition);
});

it('lets a super admin do everything', function () {
    $admin = User::factory()->create();
    $admin->assignRoleFor(RoleName::SuperAdmin);

    expect($admin->can('create', Competition::class))->toBeTrue()
        ->and($admin->can('delete', $this->competition))->toBeTrue()
        ->and($admin->can('update', $this->otherEdition))->toBeTrue()
        ->and($admin->can('delete', $this->otherRace))->toBeTrue();
});

it('lets an orga member work on its own edition only', function () {
    expect($this->orga->can('view', $this->ownEdition))->toBeTrue()
        ->and($this->orga->can('update', $this->ownEdition))->toBeTrue()
        ->and($this->orga->can('view', $this->otherEdition))->toBeFalse()
        ->and($this->orga->can('update', $this->otherEdition))->toBeFalse();
});

it('does not let an orga member create or delete editions', function () {
    expect($this->orga->can('create', Edition::class))->toBeFalse()
        ->and($this->orga->can('delete', $this->ownEdition))->toBeFalse();
});

it('binds races to the permissions of their edition', function () {
    expect($this->orga->can('view', $this->ownRace))->toBeTrue()
        ->and($this->orga->can('update', $this->ownRace))->toBeTrue()
        ->and($this->orga->can('delete', $this->ownRace))->toBeTrue()
        ->and($this->orga->can('view', $this->otherRace))->toBeFalse()
        ->and($this->orga->can('update', $this->otherRace))->toBeFalse()
        ->and($this->orga->can('delete', $this->otherRace))->toBeFalse();
});

it('binds race days to the permissions of their edition', function () {
    $ownDay = EditionDay::factory()->for($this->ownEdition)->create();
    $otherDay = EditionDay::factory()->for($this->otherEdition)->create();

    expect($this->orga->can('update', $ownDay))->toBeTrue()
        ->and($this->orga->can('update', $otherDay))->toBeFalse();
});

it('lets an orga member see its competition but not change it', function () {
    $foreign = Competition::factory()->create(['slug' => 'berlinwomen']);
    Edition::factory()->for($foreign)->create(['year' => 2028, 'slug' => '2028']);

    expect($this->orga->can('view', $this->competition))->toBeTrue()
        ->and($this->orga->can('view', $foreign))->toBeFalse()
        ->and($this->orga->can('update', $this->competition))->toBeFalse()
        ->and($this->orga->can('create', Competition::class))->toBeFalse();
});

it('lets the editorial role see but not change races', function () {
    $editor = User::factory()->create();
    $editor->assignRoleFor(RoleName::Redaktion, $this->ownEdition);

    expect($editor->can('view', $this->ownEdition))->toBeTrue()
        ->and($editor->can('update', $this->ownEdition))->toBeFalse()
        ->and($editor->can('view', $this->ownRace))->toBeFalse()
        ->and($editor->can('update', $this->ownRace))->toBeFalse();
});

it('refuses everything to a user without a role', function () {
    $nobody = User::factory()->create();

    expect($nobody->can('viewAny', Edition::class))->toBeFalse()
        ->and($nobody->can('view', $this->ownEdition))->toBeFalse()
        ->and($nobody->can('viewAny', Competition::class))->toBeFalse()
        ->and($nobody->can('viewAny', Race::class))->toBeFalse();
});

it('reserves role granting for the organiser', function () {
    $admin = User::factory()->create();
    $admin->assignRoleFor(RoleName::SuperAdmin);

    $assignment = $this->orga->roleAssignments()->first();

    expect($admin->can('create', $assignment))->toBeTrue()
        ->and($this->orga->can('create', $assignment))->toBeFalse()
        ->and($this->orga->can('delete', $assignment))->toBeFalse();
});

it('lets an orga member look people up but not change them', function () {
    $someone = User::factory()->create();

    expect($this->orga->can('viewAny', User::class))->toBeTrue()
        ->and($this->orga->can('view', $someone))->toBeTrue()
        ->and($this->orga->can('update', $someone))->toBeFalse()
        ->and($this->orga->can('delete', $someone))->toBeFalse();
});

it('never lets anyone delete their own account from the admin panel', function () {
    $admin = User::factory()->create();
    $admin->assignRoleFor(RoleName::SuperAdmin);

    // Gate::before would wave the super admin through, so the policy is asked
    // directly here.
    expect(app(UserPolicy::class)->delete($admin, $admin))->toBeFalse();
});

it('narrows a list query to the own editions', function () {
    $visible = Edition::query()->visibleTo($this->orga)->pluck('id');

    expect($visible->all())->toBe([$this->ownEdition->id]);
});

it('narrows a race list query to the own editions', function () {
    $visible = Race::query()->visibleTo($this->orga)->pluck('id');

    expect($visible->all())->toBe([$this->ownRace->id]);
});

it('does not narrow the list for a globally permitted user', function () {
    $global = User::factory()->create();
    $global->assignRoleFor(RoleName::Orga);

    expect(Edition::query()->visibleTo($global)->count())->toBe(2)
        ->and(Race::query()->visibleTo($global)->count())->toBe(2);
});
