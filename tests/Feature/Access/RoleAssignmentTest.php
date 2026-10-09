<?php

use App\Domain\Access\Enums\RoleName;
use App\Domain\Competition\Models\Edition;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Roles are always granted in a context (specification chapter 3 and 8):
 * globally, per edition or per org unit. A global grant outranks a scoped one.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('limits a scoped role to its own scope', function () {
    $user = User::factory()->create();
    $own = Edition::factory()->create(['year' => 2027, 'slug' => '2027']);
    $other = Edition::factory()->create(['year' => 2027, 'slug' => '2027']);

    $user->assignRoleFor(RoleName::Orga, $own);

    expect($user->hasRoleFor(RoleName::Orga, $own))->toBeTrue()
        ->and($user->hasRoleFor(RoleName::Orga, $other))->toBeFalse()
        ->and($user->hasRoleFor(RoleName::Orga))->toBeFalse();
});

it('lets a global role satisfy a scoped check', function () {
    $user = User::factory()->create();
    $edition = Edition::factory()->create();

    $user->assignRoleFor(RoleName::Orga);

    expect($user->hasRoleFor(RoleName::Orga))->toBeTrue()
        ->and($user->hasRoleFor(RoleName::Orga, $edition))->toBeTrue();
});

it('does not confuse two roles in the same scope', function () {
    $user = User::factory()->create();
    $edition = Edition::factory()->create();

    $user->assignRoleFor(RoleName::Redaktion, $edition);

    expect($user->hasRoleFor(RoleName::Redaktion, $edition))->toBeTrue()
        ->and($user->hasRoleFor(RoleName::Orga, $edition))->toBeFalse();
});

it('grants the same role twice without creating a duplicate', function () {
    $user = User::factory()->create();
    $edition = Edition::factory()->create();

    $user->assignRoleFor(RoleName::Orga, $edition);
    $user->assignRoleFor(RoleName::Orga, $edition);

    expect($user->roleAssignments()->count())->toBe(1);
});

it('tells global and scoped grants of one role apart', function () {
    $user = User::factory()->create();
    $edition = Edition::factory()->create();

    $user->assignRoleFor(RoleName::Orga);
    $user->assignRoleFor(RoleName::Orga, $edition);

    expect($user->roleAssignments()->count())->toBe(2);
});

it('revokes a role only in the given scope', function () {
    $user = User::factory()->create();
    $one = Edition::factory()->create(['year' => 2027, 'slug' => '2027']);
    $two = Edition::factory()->create(['year' => 2028, 'slug' => '2028']);

    $user->assignRoleFor(RoleName::Orga, $one);
    $user->assignRoleFor(RoleName::Orga, $two);
    $user->removeRoleFor(RoleName::Orga, $one);

    expect($user->hasRoleFor(RoleName::Orga, $one))->toBeFalse()
        ->and($user->hasRoleFor(RoleName::Orga, $two))->toBeTrue();
});

it('lists the editions a user holds a role in', function () {
    $user = User::factory()->create();
    $one = Edition::factory()->create(['year' => 2027, 'slug' => '2027']);
    $two = Edition::factory()->create(['year' => 2028, 'slug' => '2028']);
    Edition::factory()->create(['year' => 2029, 'slug' => '2029']);

    $user->assignRoleFor(RoleName::Orga, $one);
    $user->assignRoleFor(RoleName::Redaktion, $two);

    expect($user->scopeIdsFor(Edition::class)->all())->toEqualCanonicalizing([$one->id, $two->id])
        ->and($user->scopeIdsFor(Edition::class, [RoleName::Orga])->all())->toBe([$one->id]);
});

it('reports the role names that apply in a scope', function () {
    $user = User::factory()->create();
    $edition = Edition::factory()->create();
    $other = Edition::factory()->create(['year' => 2028, 'slug' => '2028']);

    $user->assignRoleFor(RoleName::Helfende);
    $user->assignRoleFor(RoleName::Orga, $edition);

    expect($user->roleNamesFor($edition)->all())->toEqualCanonicalizing(['helfende', 'orga'])
        ->and($user->roleNamesFor($other)->all())->toBe(['helfende'])
        ->and($user->roleNamesFor()->all())->toBe(['helfende']);
});

it('lets an edition scoped orga member into the orga panel', function () {
    $user = User::factory()->create();
    $user->assignRoleFor(RoleName::Orga, Edition::factory()->create());

    expect($user->canAccessPanel(filament()->getPanel('orga')))->toBeTrue()
        ->and($user->canAccessPanel(filament()->getPanel('admin')))->toBeFalse();
});
