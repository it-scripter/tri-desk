<?php

use App\Domain\Access\Enums\RoleName;
use App\Domain\Competition\Models\AgeGroupScheme;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use App\Filament\Admin\Resources\AgeGroupSchemes\Pages\CreateAgeGroupScheme;
use App\Filament\Orga\Resources\Editions\Pages\EditEdition;
use App\Filament\Orga\Resources\Races\Pages\EditRace;
use App\Filament\Shared\MoneyInput;
use App\Filament\Shared\RelationManagers\CancellationRulesRelationManager;
use App\Filament\Shared\RelationManagers\PriceTiersRelationManager;
use App\Filament\Shared\RelationManagers\RaceOptionsRelationManager;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * W-03, W-04, W-05 in the panels: the orga team keeps prices, options and
 * cancellation rules of its edition; the super admin keeps the age group
 * schemes. Amounts are entered in euros and stored in cents.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->edition = Edition::factory()->current()->create(['year' => 2027, 'slug' => '2027']);
    $this->race = Race::factory()->for($this->edition)->create(['name' => 'Volkstriathlon']);

    $this->orga = User::factory()->create();
    $this->orga->assignRoleFor(RoleName::Orga, $this->edition);

    $this->actingAs($this->orga);

    Filament::setCurrentPanel('orga');
});

it('converts euros to cents without float errors', function (string $input, int $cents) {
    expect(MoneyInput::toCents($input))->toBe($cents);
})->with([
    ['45', 4500],
    ['45.5', 4550],
    ['45,50', 4550],
    ['0.29', 29],
    ['12.345', 1234],
]);

it('lets the orga team add a price tier in euros', function () {
    livewire(PriceTiersRelationManager::class, [
        'ownerRecord' => $this->race,
        'pageClass' => EditRace::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'name' => 'Early Bird',
            'amount_cents' => '35,50',
            'valid_until' => '2027-03-31 23:59',
        ])
        ->assertHasNoFormErrors();

    expect($this->race->priceTiers()->sole()->amount_cents)->toBe(3550);
});

it('refuses an overlapping price tier in the form', function () {
    PriceTier::factory()->for($this->race)->create([
        'valid_from' => '2027-04-01 00:00',
        'valid_until' => '2027-06-20 23:59',
    ]);

    livewire(PriceTiersRelationManager::class, [
        'ownerRecord' => $this->race,
        'pageClass' => EditRace::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'name' => 'Doppelt',
            'amount_cents' => '40',
            'valid_from' => '2027-05-01 00:00',
        ])
        ->assertHasFormErrors(['valid_from']);

    expect($this->race->priceTiers()->count())->toBe(1);
});

it('lets the orga team add an option with sizes restricted to a race', function () {
    livewire(RaceOptionsRelationManager::class, [
        'ownerRecord' => $this->edition,
        'pageClass' => EditEdition::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'name' => 'Funktionsshirt',
            'price_cents' => '18',
            'races' => [$this->race->id],
            'variants' => [
                ['name' => 'S', 'quota' => null],
                ['name' => 'M', 'quota' => 100],
            ],
            'is_active' => true,
        ])
        ->assertHasNoFormErrors();

    $option = $this->edition->raceOptions()->sole();

    expect($option->price_cents)->toBe(1800)
        ->and($option->races->pluck('id')->all())->toBe([$this->race->id])
        ->and($option->variants->pluck('name')->all())->toBe(['S', 'M']);
});

it('lets the orga team add a cancellation step', function () {
    livewire(CancellationRulesRelationManager::class, [
        'ownerRecord' => $this->edition,
        'pageClass' => EditEdition::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'valid_until' => '2027-04-30 23:59',
            'refund_percent' => 100,
            'fee_cents' => '5',
        ])
        ->assertHasNoFormErrors();

    $rule = $this->edition->cancellationRules()->sole();

    expect($rule->refund_percent)->toBe(100)
        ->and($rule->fee_cents)->toBe(500);
});

it('lists the price tiers of the race', function () {
    $tier = PriceTier::factory()->for($this->race)->create(['name' => 'Early Bird', 'amount_cents' => 3500]);
    $foreign = PriceTier::factory()->create(['name' => 'Fremd']);

    livewire(PriceTiersRelationManager::class, [
        'ownerRecord' => $this->race,
        'pageClass' => EditRace::class,
    ])
        ->assertCanSeeTableRecords([$tier])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('refuses an amount with more than two decimals', function () {
    livewire(PriceTiersRelationManager::class, [
        'ownerRecord' => $this->race,
        'pageClass' => EditRace::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'name' => 'Krumm',
            'amount_cents' => '12.345',
        ])
        ->assertHasFormErrors(['amount_cents']);
});

it('lets the super admin create an age group scheme', function () {
    $admin = User::factory()->create();
    $admin->assignRoleFor(RoleName::SuperAdmin);
    $this->actingAs($admin);
    Filament::setCurrentPanel('admin');

    livewire(CreateAgeGroupScheme::class)
        ->fillForm([
            'name' => 'DTU',
            'slug' => 'dtu',
            'diverse_scoring' => 'as_chosen',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(AgeGroupScheme::query()->where('slug', 'dtu')->sole()->diverse_scoring->value)->toBe('as_chosen');
});

it('keeps the age group schemes away from the orga team', function () {
    $this->get('/admin/age-group-schemes')->assertForbidden();
});
