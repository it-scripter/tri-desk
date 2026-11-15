<?php

use App\Domain\Competition\Enums\RegistrationStatus;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceOption;
use App\Domain\Competition\Models\Registration;
use App\Domain\Messaging\Mail\RegistrationConfirmation;
use Illuminate\Support\Facades\Mail;

/**
 * W-10: the public registration form, step by step - race, persons, options,
 * consents, summary. Each step validates on the server; the registration
 * service has the last word on every rule.
 */
beforeEach(function () {
    $this->travelTo('2027-03-01 12:00:00');
    Mail::fake();

    $this->race = openRace(['name' => 'Volkstriathlon']);
    $this->slug = $this->race->edition->competition->slug;
    $this->base = "/{$this->slug}/anmeldung";
});

/**
 * @return array<string, mixed>
 */
function personInput(array $overrides = []): array
{
    return array_replace([
        'contact' => ['first_name' => 'Erika', 'last_name' => 'Muster', 'email' => 'erika@example.org'],
        'participants' => [[
            'first_name' => 'Erika',
            'last_name' => 'Muster',
            'birth_date' => '1987-05-10',
            'gender' => 'w',
        ]],
    ], $overrides);
}

it('lists the open races with their current price', function () {
    $this->get($this->base)
        ->assertSuccessful()
        ->assertSee('Volkstriathlon')
        ->assertSee('45,00');
});

it('tells when registration is not open', function () {
    $this->travelTo('2027-02-01 12:00:00');

    $this->get($this->base)
        ->assertSuccessful()
        ->assertSee('zurzeit keine Anmeldung möglich');
});

it('walks through every step and stores the registration', function () {
    $this->post($this->base, ['race' => $this->race->id])->assertRedirect("{$this->base}/personen");
    $this->get("{$this->base}/personen")->assertSuccessful()->assertSee('Schritt 2 von 4');

    $this->post("{$this->base}/personen", personInput())->assertRedirect("{$this->base}/zustimmungen");

    $this->post("{$this->base}/zustimmungen", ['terms' => '1', 'privacy' => '1', 'hide_name' => '1'])
        ->assertRedirect("{$this->base}/pruefen");

    $this->get("{$this->base}/pruefen")
        ->assertSuccessful()
        ->assertSee('Erika Muster')
        ->assertSee('45,00');

    $this->travel(10)->seconds();

    $this->post("{$this->base}/pruefen")->assertRedirect("{$this->base}/eingegangen");

    $registration = Registration::query()->sole();

    expect($registration->status)->toBe(RegistrationStatus::Reserved)
        ->and($registration->hide_name_in_lists)->toBeTrue()
        ->and($registration->terms_version)->toBe(__('registration_form.consents.terms.version'))
        ->and($registration->privacy_acknowledged_at)->not->toBeNull()
        ->and($registration->participants->sole()->fullName())->toBe('Erika Muster');

    $this->get("{$this->base}/eingegangen")
        ->assertSuccessful()
        ->assertSee('03.03.2027, 12:00')
        ->assertSee('erika@example.org');

    Mail::assertQueued(RegistrationConfirmation::class, fn ($mail) => $mail->hasTo('erika@example.org'));
});

it('registers a relay with all members and an optional slot left empty', function () {
    $relay = Race::factory()->for($this->race->edition)->relay()->create([
        'team_size_min' => 3,
        'team_size_max' => 4,
    ]);
    PriceTier::factory()->for($relay)->create(['amount_cents' => 9000]);

    $this->post($this->base, ['race' => $relay->id]);

    $this->get("{$this->base}/personen")
        ->assertSee('Mitglied 3')
        ->assertSee('Mitglied 4 (optional)');

    $this->post("{$this->base}/personen", personInput([
        'team_name' => 'Die Drei',
        'participants' => [
            ['first_name' => 'Anna', 'last_name' => 'Schwimm', 'birth_date' => '1990-01-01', 'gender' => 'w'],
            ['first_name' => 'Ben', 'last_name' => 'Rad', 'birth_date' => '1990-01-01', 'gender' => 'm'],
            ['first_name' => 'Clara', 'last_name' => 'Lauf', 'birth_date' => '1990-01-01', 'gender' => 'w'],
            ['first_name' => '', 'last_name' => '', 'birth_date' => '', 'gender' => ''],
        ],
    ]))->assertRedirect("{$this->base}/zustimmungen");

    $this->post("{$this->base}/zustimmungen", ['terms' => '1', 'privacy' => '1']);
    $this->travel(10)->seconds();
    $this->post("{$this->base}/pruefen");

    $registration = Registration::query()->sole();

    expect($registration->team_name)->toBe('Die Drei')
        ->and($registration->team_classification?->value)->toBe('mixed')
        ->and($registration->participants->pluck('first_name')->all())->toBe(['Anna', 'Ben', 'Clara'])
        ->and($registration->amount_cents)->toBe(9000);
});

it('validates the persons step and keeps the input', function () {
    $this->post($this->base, ['race' => $this->race->id]);

    $this->from("{$this->base}/personen")
        ->post("{$this->base}/personen", personInput(['contact' => ['first_name' => 'Erika', 'last_name' => '', 'email' => 'kein-mail']]))
        ->assertRedirect("{$this->base}/personen")
        ->assertSessionHasErrors(['contact.last_name', 'contact.email'])
        ->assertSessionHasInput('contact.first_name', 'Erika');
});

it('shows a refusal of the registration rules at the field it is about', function () {
    $this->post($this->base, ['race' => $this->race->id]);

    // A minor without guardian (W-17).
    $this->from("{$this->base}/personen")
        ->post("{$this->base}/personen", personInput(['participants' => [[
            'first_name' => 'Kim', 'last_name' => 'Jung', 'birth_date' => '2012-04-01', 'gender' => 'w',
        ]]]))
        ->assertRedirect("{$this->base}/personen")
        ->assertSessionHasErrors(['participants.0.guardian_name']);
});

it('refuses a duplicate already in the persons step', function () {
    Registration::factory()->for($this->race)->create()
        ->participants()->create(['first_name' => 'Erika', 'last_name' => 'Muster', 'birth_date' => '1987-05-10', 'gender' => 'w']);

    $this->post($this->base, ['race' => $this->race->id]);

    $this->from("{$this->base}/personen")
        ->post("{$this->base}/personen", personInput())
        ->assertSessionHasErrors(['participants.0.last_name']);
});

it('offers the gender d only where the race allows it', function () {
    $this->post($this->base, ['race' => $this->race->id]);

    $this->from("{$this->base}/personen")
        ->post("{$this->base}/personen", personInput(['participants' => [[
            'first_name' => 'Kim', 'last_name' => 'Divers', 'birth_date' => '1990-01-01', 'gender' => 'd',
        ]]]))
        ->assertSessionHasErrors(['participants.0.gender']);
});

it('books options with a variant', function () {
    $shirt = RaceOption::factory()->for($this->race->edition)->create(['name' => 'Funktionsshirt', 'price_cents' => 1800]);
    $size = $shirt->variants()->create(['name' => 'M']);

    $this->post($this->base, ['race' => $this->race->id]);
    $this->post("{$this->base}/personen", personInput())->assertRedirect("{$this->base}/optionen");

    $this->get("{$this->base}/optionen")->assertSee('Funktionsshirt')->assertSee('Schritt 3 von 5');

    $this->from("{$this->base}/optionen")
        ->post("{$this->base}/optionen", ['options' => [$shirt->id => ['selected' => '1', 'variant' => '']]])
        ->assertSessionHasErrors(['options']);

    $this->post("{$this->base}/optionen", ['options' => [$shirt->id => ['selected' => '1', 'variant' => $size->id]]])
        ->assertRedirect("{$this->base}/zustimmungen");

    $this->post("{$this->base}/zustimmungen", ['terms' => '1', 'privacy' => '1']);
    $this->get("{$this->base}/pruefen")->assertSee('Funktionsshirt (M)')->assertSee('63,00');

    $this->travel(10)->seconds();
    $this->post("{$this->base}/pruefen");

    $registration = Registration::query()->sole();

    expect($registration->amount_cents)->toBe(6300)
        ->and($registration->options->sole()->race_option_variant_id)->toBe($size->id);
});

it('requires terms and privacy notice', function () {
    $this->post($this->base, ['race' => $this->race->id]);
    $this->post("{$this->base}/personen", personInput());

    $this->from("{$this->base}/zustimmungen")
        ->post("{$this->base}/zustimmungen", ['terms' => '1'])
        ->assertSessionHasErrors(['privacy']);
});

it('shows the cancellation rules next to the consents', function () {
    $this->race->edition->cancellationRules()->create(['valid_until' => '2027-04-30 23:59:00', 'refund_percent' => 100, 'fee_cents' => 500]);

    $this->post($this->base, ['race' => $this->race->id]);
    $this->post("{$this->base}/personen", personInput());

    $this->get("{$this->base}/zustimmungen")
        ->assertSee('Storno bis 30.04.2027: 100 % Erstattung')
        ->assertSee('5,00');
});

it('sends steps without a draft back to the start', function () {
    $this->get("{$this->base}/personen")->assertRedirect($this->base);
    $this->get("{$this->base}/pruefen")->assertRedirect($this->base);
    $this->post("{$this->base}/pruefen")->assertRedirect($this->base);

    expect(Registration::query()->count())->toBe(0);
});

it('refuses a race of another edition', function () {
    $foreign = openRace();

    $this->post($this->base, ['race' => $foreign->id])->assertSessionHasErrors(['race']);
});

it('drops the draft when the honeypot is filled in', function () {
    $this->post($this->base, ['race' => $this->race->id]);

    $this->post("{$this->base}/personen", personInput(['website' => 'https://spam.example']))
        ->assertRedirect($this->base)
        ->assertSessionHasErrors(['registration']);

    $this->get("{$this->base}/pruefen")->assertRedirect($this->base);
});

it('refuses a submit faster than any person can fill in the form', function () {
    $this->post($this->base, ['race' => $this->race->id]);
    $this->post("{$this->base}/personen", personInput());
    $this->post("{$this->base}/zustimmungen", ['terms' => '1', 'privacy' => '1']);

    $this->post("{$this->base}/pruefen")->assertSessionHasErrors(['registration']);

    expect(Registration::query()->count())->toBe(0);
});

it('shows a refusal at submit, e.g. when the race sold out meanwhile', function () {
    $this->race->update(['participant_limit' => 1]);

    $this->post($this->base, ['race' => $this->race->id]);
    $this->post("{$this->base}/personen", personInput());
    $this->post("{$this->base}/zustimmungen", ['terms' => '1', 'privacy' => '1']);

    Registration::factory()->for($this->race)->create();
    $this->travel(10)->seconds();

    $this->post("{$this->base}/pruefen")
        ->assertRedirect("{$this->base}/pruefen")
        ->assertSessionHasErrors(['registration' => 'Der Wettbewerb ist ausgebucht.']);
});

it('limits how often the form can be sent', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->post("{$this->base}/pruefen");
    }

    $this->post("{$this->base}/pruefen")->assertTooManyRequests();
});

it('links from the competition page to the registration', function () {
    $this->get("/{$this->slug}")->assertSee('Zur Anmeldung');
});
