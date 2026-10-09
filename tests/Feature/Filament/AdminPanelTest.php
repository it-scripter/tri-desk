<?php

use App\Domain\Access\Enums\RoleName;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\Race;
use App\Filament\Admin\Resources\Competitions\Pages\CreateCompetition;
use App\Filament\Admin\Resources\Editions\Pages\EditEdition;
use App\Filament\Admin\Resources\Editions\RelationManagers\RacesRelationManager;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\User;
use Database\Seeders\RoleSeeder;

use function Pest\Livewire\livewire;

/**
 * The organiser's panel (specification 6.2): competitions, editions, accounts
 * and roles, plus the audit log.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRoleFor(RoleName::SuperAdmin);

    $this->actingAs($this->admin);
});

it('lists competitions with their current edition', function () {
    $competition = Competition::factory()->create(['name' => 'Volkstriathlon', 'slug' => 'volkstriathlon']);
    Edition::factory()->for($competition)->current()->create([
        'year' => 2027,
        'number' => 38,
        'slug' => '2027',
    ]);

    $this->get('/admin/competitions')
        ->assertSuccessful()
        ->assertSee('Volkstriathlon')
        ->assertSee('38. Volkstriathlon 2027');
});

it('creates a competition through the form', function () {
    livewire(CreateCompetition::class)
        ->fillForm([
            'name' => 'Schüler-Duathlon',
            'slug' => 'schueler-duathlon',
            'is_active' => true,
            'sort' => 3,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Competition::query()->where('slug', 'schueler-duathlon')->exists())->toBeTrue();
});

it('refuses a reserved slug', function () {
    livewire(CreateCompetition::class)
        ->fillForm([
            'name' => 'Verwaltung',
            'slug' => 'admin',
        ])
        ->call('create')
        ->assertHasFormErrors(['slug']);

    expect(Competition::query()->where('slug', 'admin')->exists())->toBeFalse();
});

it('shows the edition with its days and races', function () {
    $edition = Edition::factory()->create(['year' => 2027, 'number' => 38, 'slug' => '2027']);

    $this->get("/admin/editions/{$edition->id}/edit")
        ->assertSuccessful()
        ->assertSee('Wettkampftage')
        ->assertSee('Wettbewerbe');
});

it('keeps the edition number and display name editable', function () {
    $edition = Edition::factory()->create(['year' => 2024, 'slug' => '2024']);

    livewire(EditEdition::class, ['record' => $edition->id])
        ->fillForm([
            'display_name' => 'BerlinMan',
            'number' => 17,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($edition->fresh()->title())->toBe('BerlinMan 2024')
        ->and($edition->fresh()->number)->toBe(17);
});

it('lists users with their roles and scope', function () {
    $edition = Edition::factory()->create(['year' => 2027, 'number' => 38, 'slug' => '2027']);

    $member = User::factory()->create(['first_name' => 'Olga', 'last_name' => 'Orga']);
    $member->assignRoleFor(RoleName::Orga, $edition);

    $this->get('/admin/users')
        ->assertSuccessful()
        ->assertSee('Olga Orga')
        ->assertSee('Orga-Team');
});

it('keeps the password when the field is left empty', function () {
    $user = User::factory()->create();
    $before = $user->password;

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm(['first_name' => 'Geändert', 'password' => ''])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->password)->toBe($before)
        ->and($user->fresh()->first_name)->toBe('Geändert');
});

it('keeps a race list for the edition', function () {
    $edition = Edition::factory()->create(['year' => 2027, 'slug' => '2027']);
    Race::factory()->for($edition)->quadrathlon()->create(['name' => 'Quadrathlon']);

    livewire(RacesRelationManager::class, [
        'ownerRecord' => $edition,
        'pageClass' => EditEdition::class,
    ])
        ->assertCanSeeTableRecords($edition->races)
        ->assertSee('Schwimmen – Rad – Kanu – Lauf');
});
