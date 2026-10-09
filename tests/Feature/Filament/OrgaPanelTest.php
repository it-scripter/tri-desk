<?php

use App\Domain\Access\Enums\RoleName;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\Race;
use App\Filament\Orga\Resources\Editions\Pages\ListEditions;
use App\Filament\Orga\Resources\Races\Pages\CreateRace;
use App\Filament\Orga\Resources\Races\Pages\ListRaces;
use App\Filament\Orga\Widgets\EditionOverview;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * The orga team's panel (specification 6.3). It must only ever show the own
 * edition - rows of other editions stay out of the query, they are not merely
 * hidden (CLAUDE.md).
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $competition = Competition::factory()->create(['name' => 'Volkstriathlon', 'slug' => 'volkstriathlon']);

    $this->ownEdition = Edition::factory()->for($competition)->current()->create([
        'year' => 2027,
        'number' => 38,
        'slug' => '2027',
    ]);

    $this->otherEdition = Edition::factory()->for($competition)->archived()->create([
        'year' => 2026,
        'number' => 37,
        'slug' => '2026',
    ]);

    $this->ownRace = Race::factory()->for($this->ownEdition)->create(['name' => 'Eigener Wettbewerb']);
    $this->otherRace = Race::factory()->for($this->otherEdition)->create(['name' => 'Fremder Wettbewerb']);

    $this->orga = User::factory()->create();
    $this->orga->assignRoleFor(RoleName::Orga, $this->ownEdition);

    $this->actingAs($this->orga);

    // Livewire components are tested outside a request, so the panel their
    // routes belong to has to be named explicitly - otherwise Filament
    // resolves against the default panel (/admin).
    Filament::setCurrentPanel('orga');
});

it('lists only the own edition', function () {
    livewire(ListEditions::class)
        ->assertCanSeeTableRecords([$this->ownEdition])
        ->assertCanNotSeeTableRecords([$this->otherEdition]);
});

it('lists only the races of the own edition', function () {
    livewire(ListRaces::class)
        ->assertCanSeeTableRecords([$this->ownRace])
        ->assertCanNotSeeTableRecords([$this->otherRace]);
});

it('refuses to open a foreign edition directly', function () {
    $this->get("/orga/editions/{$this->otherEdition->id}/edit")->assertNotFound();
});

it('refuses to open a foreign race directly', function () {
    $this->get("/orga/races/{$this->otherRace->id}/edit")->assertNotFound();
});

it('opens the own edition', function () {
    $this->get("/orga/editions/{$this->ownEdition->id}/edit")->assertSuccessful();
});

it('does not offer creating an edition', function () {
    $this->get('/orga/editions/create')->assertNotFound();
});

it('lets the orga team add a race to the own edition', function () {
    livewire(CreateRace::class)
        ->fillForm([
            'edition_id' => $this->ownEdition->id,
            'name' => 'Volxtri4kids',
            'slug' => 'volxtri4kids',
            'type' => 'individual',
            'discipline_sequence' => [['discipline' => 'swim'], ['discipline' => 'bike'], ['discipline' => 'run']],
            'is_public' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($this->ownEdition->races()->where('slug', 'volxtri4kids')->exists())->toBeTrue();
});

it('keeps the discipline sequence in the given order', function () {
    livewire(CreateRace::class)
        ->fillForm([
            'edition_id' => $this->ownEdition->id,
            'name' => 'Schüler-Duathlon',
            'slug' => 'schueler-duathlon',
            'type' => 'individual',
            'discipline_sequence' => [['discipline' => 'run'], ['discipline' => 'bike'], ['discipline' => 'run']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $race = Race::query()->where('slug', 'schueler-duathlon')->sole();

    expect($race->disciplineSequenceLabel())->toBe('Lauf – Rad – Lauf');
});

it('shows the current edition on the dashboard', function () {
    // The widget is a lazily loaded Livewire component, so it is rendered
    // directly instead of looking for it in the page HTML.
    livewire(EditionOverview::class)
        ->assertSee('38. Volkstriathlon 2027')
        ->assertSee('Geplant');
});

it('tells an account with no edition that nothing is assigned', function () {
    // The realistic case: the account exists, the organiser has not appointed
    // the person to an edition yet.
    $fresh = User::factory()->create();

    $this->actingAs($fresh);

    livewire(EditionOverview::class)
        ->assertSee('Keine Ausgabe zugeordnet');
});

it('tells a user without an edition that nothing is assigned', function () {
    $fresh = User::factory()->create();
    $fresh->assignRoleFor(RoleName::Orga);
    $fresh->removeRoleFor(RoleName::Orga);
    $fresh->assignRoleFor(RoleName::Teamleitung, $this->ownEdition);

    // A team leader reaches the team panel, not the orga panel.
    $this->actingAs($fresh)->get('/orga')->assertForbidden();
});

it('shows the team panel placeholder to a team leader', function () {
    $leader = User::factory()->create();
    $leader->assignRoleFor(RoleName::Teamleitung, $this->ownEdition);

    // /team redirects to the panel's home page.
    $this->actingAs($leader)
        ->get('/team')
        ->assertRedirect('/team/dashboard');

    $this->actingAs($leader)
        ->get('/team/dashboard')
        ->assertSuccessful()
        ->assertSee('Teamleitung')
        ->assertSee('Helferportal');
});
