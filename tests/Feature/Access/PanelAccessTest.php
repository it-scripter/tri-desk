<?php

use App\Domain\Access\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Panels are separated by role (specification 6.3): /admin for the organiser,
 * /orga for an edition's orga team, /team for team leaders.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('lets a super admin into every panel', function () {
    $user = User::factory()->create();
    $user->assignRoleFor(RoleName::SuperAdmin);

    $this->actingAs($user);

    expect($user->canAccessPanel(filament()->getPanel('admin')))->toBeTrue()
        ->and($user->canAccessPanel(filament()->getPanel('orga')))->toBeTrue()
        ->and($user->canAccessPanel(filament()->getPanel('team')))->toBeTrue();
});

it('keeps a user without any role out of every panel', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(filament()->getPanel('admin')))->toBeFalse()
        ->and($user->canAccessPanel(filament()->getPanel('orga')))->toBeFalse()
        ->and($user->canAccessPanel(filament()->getPanel('team')))->toBeFalse();
});

it('does not let an orga member reach the organiser panel', function () {
    $user = User::factory()->create();
    $user->assignRoleFor(RoleName::Orga);

    expect($user->canAccessPanel(filament()->getPanel('orga')))->toBeTrue()
        ->and($user->canAccessPanel(filament()->getPanel('admin')))->toBeFalse()
        ->and($user->canAccessPanel(filament()->getPanel('team')))->toBeFalse();
});

it('lets a team leader into the team panel only', function () {
    $user = User::factory()->create();
    $user->assignRoleFor(RoleName::Teamleitung);

    expect($user->canAccessPanel(filament()->getPanel('team')))->toBeTrue()
        ->and($user->canAccessPanel(filament()->getPanel('orga')))->toBeFalse()
        ->and($user->canAccessPanel(filament()->getPanel('admin')))->toBeFalse();
});

it('redirects an anonymous visitor from every panel to its login', function (string $path) {
    $this->get($path)->assertRedirect("{$path}/login");
})->with(['/admin', '/orga', '/team']);

it('refuses a signed in user without a role', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});
