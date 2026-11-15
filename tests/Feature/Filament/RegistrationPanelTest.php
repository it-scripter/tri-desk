<?php

use App\Domain\Access\Enums\RoleName;
use App\Domain\Competition\Enums\RegistrationStatus;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\Registration;
use App\Domain\Competition\Models\RegistrationParticipant;
use App\Filament\Orga\Resources\Races\Pages\CreateRace;
use App\Filament\Orga\Resources\Registrations\Pages\ListRegistrations;
use App\Filament\Orga\Resources\Registrations\Pages\ViewRegistration;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * The orga team's registration list (specification 6.3): own edition only,
 * with details and cancelling. Plus the race settings registration needs
 * (W-12, W-18).
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->race = openRace(['name' => 'Volkstriathlon']);
    $this->edition = $this->race->edition;

    $this->orga = User::factory()->create();
    $this->orga->assignRoleFor(RoleName::Orga, $this->edition);
    $this->actingAs($this->orga);

    Filament::setCurrentPanel('orga');

    $this->own = Registration::factory()->for($this->race)->create();
    RegistrationParticipant::factory()->for($this->own)->create(['first_name' => 'Erika', 'last_name' => 'Muster']);

    $this->other = Registration::factory()->for(Race::factory())->create();
});

it('lists the registrations of the own edition only', function () {
    livewire(ListRegistrations::class)
        ->assertCanSeeTableRecords([$this->own])
        ->assertCanNotSeeTableRecords([$this->other])
        ->assertSee('Erika Muster');
});

it('finds a registration by participant name', function () {
    $second = Registration::factory()->for($this->race)->create();
    RegistrationParticipant::factory()->for($second)->create(['first_name' => 'Kim', 'last_name' => 'Anders']);

    livewire(ListRegistrations::class)
        ->searchTable('Muster')
        ->assertCanSeeTableRecords([$this->own])
        ->assertCanNotSeeTableRecords([$second]);
});

it('refuses to open a foreign registration', function () {
    $this->get("/orga/registrations/{$this->other->id}")->assertNotFound();
});

it('shows a registration with its participants', function () {
    $this->get("/orga/registrations/{$this->own->id}")
        ->assertSuccessful()
        ->assertSee('Erika Muster');
});

it('cancels a registration and frees the place', function () {
    livewire(ViewRegistration::class, ['record' => $this->own->getRouteKey()])
        ->callAction('cancel')
        ->assertHasNoActionErrors();

    expect($this->own->fresh()->status)->toBe(RegistrationStatus::Cancelled);
});

it('keeps the team settings of a relay', function () {
    livewire(CreateRace::class)
        ->fillForm([
            'edition_id' => $this->edition->id,
            'name' => 'Staffel',
            'slug' => 'staffel',
            'type' => 'relay',
            'team_size_min' => 3,
            'team_size_max' => 3,
            'mixed_min_male' => 1,
            'mixed_min_female' => 2,
            'discipline_sequence' => [['discipline' => 'swim'], ['discipline' => 'bike'], ['discipline' => 'run']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $relay = Race::query()->where('slug', 'staffel')->sole();

    expect($relay->team_size_min)->toBe(3)
        ->and($relay->mixed_min_female)->toBe(2);
});
