<?php

use App\Domain\Access\Enums\RoleName;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Registration;
use App\Domain\Messaging\Mail\RegistrationConfirmation;
use App\Filament\Orga\Resources\Editions\Pages\EditEdition;
use App\Filament\Orga\Resources\Registrations\Pages\ListRegistrations;
use App\Http\Registration\RegistrationTestMode;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

use function Pest\Livewire\livewire;

/**
 * The test mode of the registration form: the orga team plays the form
 * through before registration opens, at a moment of its choice. Test
 * registrations are stored and mailed, but marked, never hold a place and
 * can be deleted.
 */
beforeEach(function () {
    // Two weeks before registration opens on 15.02.2027.
    $this->travelTo('2027-02-01 12:00:00');
    Mail::fake();
    $this->seed(RoleSeeder::class);

    $this->race = openRace(['name' => 'Volkstriathlon', 'participant_limit' => 1]);
    $this->edition = $this->race->edition;
    $this->race->priceTiers()->update(['name' => 'Early Bird', 'amount_cents' => 3500, 'valid_until' => '2027-03-31 23:59:00']);
    PriceTier::factory()->for($this->race)->create(['name' => 'Normal', 'amount_cents' => 4500, 'valid_from' => '2027-04-01 00:00:00']);

    $this->slug = $this->edition->competition->slug;
    $this->base = "/{$this->slug}/anmeldung";

    $this->orga = User::factory()->create();
    $this->orga->assignRoleFor(RoleName::Orga, $this->edition);
});

function startTest(object $test, string $at = '2027-02-15 10:00:00'): void
{
    $test->actingAs($test->orga);
    RegistrationTestMode::start(session()->driver(), $test->edition, Carbon::parse($at), $test->orga);
}

function playThrough(object $test, array $person = []): void
{
    $test->post($test->base, ['race' => $test->race->id]);
    $test->post("{$test->base}/personen", personInput($person));
    $test->post("{$test->base}/zustimmungen", ['terms' => '1', 'privacy' => '1']);
    $test->travel(10)->seconds();
    $test->post("{$test->base}/pruefen");
}

it('keeps the form closed before registration opens without the test mode', function () {
    $this->actingAs($this->orga)
        ->get($this->base)
        ->assertSee('zurzeit keine Anmeldung möglich');
});

it('opens the test from the orga panel in a new tab', function () {
    $this->actingAs($this->orga);
    Filament::setCurrentPanel('orga');

    livewire(EditEdition::class, ['record' => $this->edition->getRouteKey()])
        ->assertActionVisible('testRegistration')
        ->assertActionShouldOpenUrlInNewTab('testRegistration')
        ->assertActionHasUrl('testRegistration', URL::temporarySignedRoute(
            'competition.registration.test.start',
            now()->addHours(12),
            ['competition' => $this->slug, 'edition' => $this->edition->id],
        ));
});

it('starts the test at the registration start and opens the form', function () {
    $link = URL::temporarySignedRoute('competition.registration.test.start', now()->addHour(), [
        'competition' => $this->slug,
        'edition' => $this->edition->id,
    ]);

    $this->actingAs($this->orga)->get($link)->assertRedirect($this->base);

    $this->get($this->base)
        ->assertSuccessful()
        ->assertSee('Testmodus')
        ->assertSee('15.02.2027, 10:00')
        ->assertSee('35,00');
});

it('refuses a start link without signature, sign-in or permission', function () {
    $start = "{$this->base}/test/starten/{$this->edition->id}";
    $signed = URL::temporarySignedRoute('competition.registration.test.start', now()->addHour(), [
        'competition' => $this->slug,
        'edition' => $this->edition->id,
    ]);

    $this->actingAs($this->orga)->get($start)->assertForbidden();

    auth()->logout();
    $this->get($signed)->assertNotFound();

    $stranger = User::factory()->create();
    $this->actingAs($stranger)->get($signed)->assertForbidden();

    $this->get($this->base)->assertDontSee('Testmodus');
});

it('prices by the moment under test', function () {
    startTest($this, '2027-04-15 10:00:00');

    $this->get($this->base)->assertSee('45,00');
});

it('moves the moment under test from the banner', function () {
    startTest($this);

    $this->get($this->base)->assertSee('35,00');

    $this->from($this->base)
        ->post("{$this->base}/test/zeitpunkt", ['at' => '2027-04-15T10:00'])
        ->assertRedirect($this->base);

    $this->get($this->base)
        ->assertSee('15.04.2027, 10:00')
        ->assertSee('45,00');
});

it('stores a marked test registration and mails it with [TEST]', function () {
    startTest($this);
    playThrough($this);

    $registration = Registration::query()->sole();

    expect($registration->is_test)->toBeTrue()
        ->and($registration->amount_cents)->toBe(3500)
        ->and($registration->priceTier->name)->toBe('Early Bird')
        // The payment window runs on the real clock, so the expiry job
        // releases test registrations as well.
        ->and($registration->reserved_until->format('Y-m-d H:i'))->toBe('2027-02-03 12:00');

    Mail::assertQueued(RegistrationConfirmation::class, function (RegistrationConfirmation $mail) {
        $mail->assertSeeInHtml('Testmodus');

        return str_starts_with($mail->envelope()->subject, '[TEST] ');
    });
});

it('lets a test registration hold no place and count as no duplicate', function () {
    startTest($this);
    playThrough($this);
    playThrough($this);

    expect(Registration::query()->count())->toBe(2)
        ->and(Registration::query()->real()->count())->toBe(0)
        ->and($this->race->registrations()->holdingPlace()->count())->toBe(0);
});

it('ends the test mode on request', function () {
    startTest($this);

    $this->post("{$this->base}/test/beenden")->assertRedirect("/{$this->slug}");

    // Started in a new tab, so the page only says the tab may be closed.
    $this->get("/{$this->slug}")->assertSee('Testmodus beendet. Dieser Tab kann geschlossen werden.');

    $this->get($this->base)->assertDontSee('Testmodus')->assertSee('zurzeit keine Anmeldung möglich');
});

it('ends the test mode once the person is signed out', function () {
    startTest($this);
    auth()->logout();

    $this->get($this->base)->assertDontSee('Testmodus');
});

it('ends the test mode once the permission is gone', function () {
    startTest($this);
    $this->orga->removeRoleFor(RoleName::Orga, $this->edition);

    $this->get($this->base)->assertDontSee('Testmodus');
});

it('does not offer the test to someone without the permission', function () {
    $editor = User::factory()->create();
    $editor->assignRoleFor(RoleName::Redaktion, $this->edition);

    expect($editor->can('testRegistration', $this->edition))->toBeFalse()
        ->and($this->orga->can('testRegistration', $this->edition))->toBeTrue();
});

it('refuses the test for a closed edition', function () {
    $this->edition->update(['status' => 'archived']);

    expect($this->orga->can('testRegistration', $this->edition->fresh()))->toBeFalse();
});

it('marks test registrations in the list and lets them be deleted, real ones not', function () {
    startTest($this);
    playThrough($this);

    $test = Registration::query()->sole();
    $real = Registration::factory()->for($this->race)->create();

    expect($this->orga->can('delete', $test))->toBeTrue()
        ->and($this->orga->can('delete', $real))->toBeFalse();

    Filament::setCurrentPanel('orga');

    livewire(ListRegistrations::class)
        ->filterTable('is_test', true)
        ->assertCanSeeTableRecords([$test])
        ->assertCanNotSeeTableRecords([$real])
        ->callAction(TestAction::make('delete')->table($test));

    expect(Registration::query()->find($test->id))->toBeNull();
});

it('shows the test mark on the magic link page', function () {
    startTest($this);
    $this->post($this->base, ['race' => $this->race->id]);
    $this->post("{$this->base}/personen", personInput());
    $this->post("{$this->base}/zustimmungen", ['terms' => '1', 'privacy' => '1']);
    $this->travel(10)->seconds();
    $this->post("{$this->base}/pruefen");

    $token = null;
    Mail::assertQueued(RegistrationConfirmation::class, function (RegistrationConfirmation $mail) use (&$token) {
        $token = $mail->token;

        return true;
    });

    $this->get("{$this->base}/verwalten/{$token}")->assertSee('Testanmeldung');
});
