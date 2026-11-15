<?php

use App\Domain\Access\Enums\RoleName;
use App\Domain\Competition\Exceptions\RegistrationRejected;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\RaceField;
use App\Domain\Competition\Models\Registration;
use App\Domain\Competition\Services\EditionCopier;
use App\Domain\Competition\Services\RegistrationService;
use App\Filament\Orga\Resources\Races\Pages\EditRace;
use App\Filament\Shared\RelationManagers\RaceFieldsRelationManager;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

/**
 * W-11: additional fields per race - from the catalogue or defined freely,
 * asked once per registration or for every person.
 */
beforeEach(function () {
    $this->travelTo('2027-03-01 12:00:00');
    Mail::fake();

    $this->race = openRace(['name' => 'Volkstriathlon']);
    $this->base = '/'.$this->race->edition->competition->slug.'/anmeldung';

    RaceField::factory()->for($this->race)->required()->create(['key' => 'club', 'label' => 'Verein']);
    RaceField::factory()->for($this->race)->perRegistration()->create([
        'key' => 'expected_time',
        'label' => 'Erwartete Zeit',
        'type' => 'time',
    ]);
});

it('asks the fields in the form and validates them by type', function () {
    $this->post($this->base, ['race' => $this->race->id]);

    $this->get("{$this->base}/personen")->assertSee('Verein')->assertSee('Erwartete Zeit');

    $this->from("{$this->base}/personen")
        ->post("{$this->base}/personen", personInput(['extra' => ['expected_time' => 'bald']]))
        ->assertSessionHasErrors(['participants.0.extra.club', 'extra.expected_time']);
});

it('stores the answers at the person and at the registration', function () {
    $this->post($this->base, ['race' => $this->race->id]);
    $input = personInput(['extra' => ['expected_time' => '01:15:00']]);
    $input['participants'][0]['extra'] = ['club' => 'TSV Musterstadt'];

    $this->post("{$this->base}/personen", $input)->assertRedirect("{$this->base}/zustimmungen");
    $this->post("{$this->base}/zustimmungen", ['terms' => '1', 'privacy' => '1']);
    $this->travel(10)->seconds();
    $this->post("{$this->base}/pruefen");

    $registration = Registration::query()->sole();

    expect($registration->extra)->toBe(['expected_time' => '01:15:00'])
        ->and($registration->participants->sole()->extra)->toBe(['club' => 'TSV Musterstadt']);
});

it('drops answers to fields the race does not have', function () {
    $registration = app(RegistrationService::class)->register(registrationFor(
        $this->race,
        [participant(extra: ['club' => 'TSV', 'shoe_size' => '44'])],
        extra: ['expected_time' => '01:00:00', 'favourite_colour' => 'blau'],
    ))->registration;

    expect($registration->extra)->toBe(['expected_time' => '01:00:00'])
        ->and($registration->participants->sole()->extra)->toBe(['club' => 'TSV']);
});

it('refuses a registration without a required answer, whatever the form sent', function () {
    app(RegistrationService::class)->register(registrationFor($this->race, [participant()]));
})->throws(RegistrationRejected::class, 'Verein');

it('takes a field over from the catalogue in the orga panel', function () {
    $this->seed(RoleSeeder::class);
    $orga = User::factory()->create();
    $orga->assignRoleFor(RoleName::Orga, $this->race->edition);
    $this->actingAs($orga);
    Filament::setCurrentPanel('orga');

    livewire(RaceFieldsRelationManager::class, ['ownerRecord' => $this->race, 'pageClass' => EditRace::class])
        ->callAction(TestAction::make('fromCatalogue')->table(), ['key' => 'emergency_contact', 'is_required' => true])
        ->assertHasNoActionErrors();

    $field = $this->race->fields()->where('key', 'emergency_contact')->sole();

    expect($field->label)->toBe('Notfallkontakt (Name und Telefon)')
        ->and($field->scope->value)->toBe('registration')
        ->and($field->is_required)->toBeTrue();
});

it('copies the fields with the edition', function () {
    $this->race->edition->update(['starts_on' => '2027-06-27']);

    $copy = (new EditionCopier)->copy($this->race->edition->fresh(), 2028, null, '2028', Carbon::parse('2028-06-25'));

    expect($copy->races->sole()->fields->pluck('key')->sort()->values()->all())->toBe(['club', 'expected_time']);
});

it('binds fields to the permissions of the race', function () {
    $this->seed(RoleSeeder::class);
    $orga = User::factory()->create();
    $orga->assignRoleFor(RoleName::Orga, Edition::factory()->create(['slug' => 'fremd', 'year' => 2030]));

    expect($orga->can('update', $this->race->fields->first()))->toBeFalse();
});
