<?php

use App\Domain\Access\Enums\RoleName;
use App\Domain\Competition\Enums\EditionStatus;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Activitylog\Models\Activity;

/**
 * B-03: who changed what and when, so transfers, payments and result
 * corrections stay traceable.
 */
it('records a change with the causer and only the changed fields', function () {
    $user = User::factory()->create();
    $edition = Edition::factory()->create(['status' => EditionStatus::Planned]);

    $this->actingAs($user);
    $edition->update(['status' => EditionStatus::RegistrationOpen]);

    $activity = Activity::query()->where('subject_type', $edition->getMorphClass())->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('geändert')
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->log_name)->toBe('audit')
        ->and(array_keys($activity->attribute_changes['attributes']))->toBe(['status'])
        ->and($activity->attribute_changes['attributes']['status'])->toBe('registration_open')
        ->and($activity->attribute_changes['old']['status'])->toBe('planned');
});

it('records creation and deletion as well', function () {
    $competition = Competition::factory()->create();
    $competition->delete();

    $descriptions = Activity::query()
        ->where('subject_type', $competition->getMorphClass())
        ->where('subject_id', $competition->id)
        ->orderBy('id')
        ->pluck('description')
        ->all();

    expect($descriptions)->toBe(['erstellt', 'gelöscht']);
});

it('writes no entry when nothing actually changed', function () {
    $edition = Edition::factory()->create(['year' => 2027]);
    $before = Activity::query()->count();

    $edition->update(['year' => 2027]);

    expect(Activity::query()->count())->toBe($before);
});

it('never records credentials', function () {
    $user = User::factory()->create();

    $user->update([
        'password' => 'ein-neues-geheimnis',
        'phone' => '030 123456',
    ]);

    $activity = Activity::query()->where('subject_type', $user->getMorphClass())->latest('id')->first();
    $recorded = array_keys($activity->attribute_changes['attributes']);

    expect($recorded)->toBe(['phone'])
        ->and($recorded)->not->toContain('password')
        ->and(json_encode($activity->attribute_changes))->not->toContain('geheimnis');
});

it('records granting and revoking a role', function () {
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create();
    $member = User::factory()->create();
    $edition = Edition::factory()->create();

    $this->actingAs($admin);
    $assignment = $member->assignRoleFor(RoleName::Orga, $edition);
    $member->removeRoleFor(RoleName::Orga, $edition);

    $activities = Activity::query()
        ->where('subject_type', $assignment->getMorphClass())
        ->orderBy('id')
        ->get();

    expect($activities->pluck('description')->all())->toBe(['erstellt', 'gelöscht'])
        ->and($activities->first()->causer_id)->toBe($admin->id)
        ->and($activities->first()->attribute_changes['attributes']['scope_id'])->toBe($edition->id);
});

it('leaves the causer empty for changes made by a seeder or command', function () {
    $competition = Competition::factory()->create();

    $activity = Activity::query()->where('subject_type', $competition->getMorphClass())->latest('id')->first();

    expect($activity->causer_id)->toBeNull();
});

it('shows the audit log to a super admin only', function () {
    $this->seed(RoleSeeder::class);

    Edition::factory()->create();

    $admin = User::factory()->create();
    $admin->assignRoleFor(RoleName::SuperAdmin);

    $this->actingAs($admin)
        ->get('/admin/activities')
        ->assertSuccessful()
        ->assertSee('Aktivitätsprotokoll');
});

it('hides the audit log from an orga member', function () {
    $this->seed(RoleSeeder::class);

    $member = User::factory()->create();
    $member->assignRoleFor(RoleName::Orga, Edition::factory()->create());

    // The orga role holds no activity.view permission, so even a direct call
    // to the admin panel route is refused.
    $this->actingAs($member)
        ->get('/admin/activities')
        ->assertForbidden();
});

it('resolves permissions through the role assignment, not through the user', function () {
    $this->seed(RoleSeeder::class);

    $edition = Edition::factory()->create();
    $other = Edition::factory()->create(['year' => 2028, 'slug' => '2028']);

    $user = User::factory()->create();
    $user->assignRoleFor(RoleName::Orga, $edition);

    expect($user->hasPermissionFor('race.create', $edition))->toBeTrue()
        ->and($user->hasPermissionFor('race.create', $other))->toBeFalse()
        ->and($user->hasPermissionFor('activity.view', $edition))->toBeFalse()
        ->and($user->hasPermissionFor('gibt.es.nicht', $edition))->toBeFalse();
});
