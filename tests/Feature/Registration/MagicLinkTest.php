<?php

use App\Domain\Competition\Enums\RegistrationStatus;
use App\Domain\Competition\Services\RegistrationService;
use App\Domain\Messaging\Mail\RegistrationConfirmation;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;

/**
 * W-15: the confirmation mail carries a magic link; behind it the
 * registration can be seen and cancelled. The token is looked up by its hash
 * and only within its own competition.
 */
beforeEach(function () {
    $this->travelTo('2027-03-01 12:00:00');

    $this->race = openRace(['name' => 'Volkstriathlon']);
    $this->race->edition->cancellationRules()->create(['valid_until' => '2027-04-30 23:59:00', 'refund_percent' => 100, 'fee_cents' => 500]);
    $this->slug = $this->race->edition->competition->slug;

    $this->result = app(RegistrationService::class)->register(registrationFor($this->race));
    $this->url = "/{$this->slug}/anmeldung/verwalten/{$this->result->token}";
});

it('puts the magic link into the confirmation mail', function () {
    $mail = new RegistrationConfirmation($this->result->registration, $this->result->token);

    $mail->assertSeeInHtml($this->result->token)
        ->assertSeeInText($this->result->token)
        ->assertSeeInHtml('Erika Muster')
        ->assertSeeInHtml('03.03.2027, 12:00')
        ->assertHasSubject('Anmeldung Volkstriathlon – '.$this->race->edition->titleWithNumber());
});

it('encrypts the queued mail, since it carries the plain token', function () {
    expect(new RegistrationConfirmation($this->result->registration, $this->result->token))
        ->toBeInstanceOf(ShouldBeEncrypted::class);
});

it('shows the registration behind the link and keeps it out of search engines', function () {
    $this->get($this->url)
        ->assertSuccessful()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('noindex', false)
        ->assertSee('Erika Muster')
        ->assertSee('Reserviert')
        ->assertSee('40,00');
});

it('answers an unknown token with not found', function () {
    $this->get("/{$this->slug}/anmeldung/verwalten/".str_repeat('a', 48))->assertNotFound();
});

it('does not open a registration through another competition', function () {
    $other = openRace()->edition->competition->slug;

    $this->get("/{$other}/anmeldung/verwalten/{$this->result->token}")->assertNotFound();
});

it('cancels only after an explicit confirmation', function () {
    $this->post("{$this->url}/stornieren")->assertSessionHasErrors(['confirm']);

    expect($this->result->registration->fresh()->status)->toBe(RegistrationStatus::Reserved);

    $this->post("{$this->url}/stornieren", ['confirm' => '1'])->assertRedirect($this->url);

    expect($this->result->registration->fresh()->status)->toBe(RegistrationStatus::Cancelled);

    $this->get($this->url)
        ->assertSee('Die Anmeldung wurde storniert.')
        ->assertDontSee('Ja, ich möchte die Anmeldung verbindlich stornieren.');
});

it('tells when nothing is refunded any more', function () {
    $this->travelTo('2027-05-15 12:00:00');

    $this->get($this->url)->assertSee('keine Erstattung mehr möglich');
});

it('limits requests to the magic link page', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->get("/{$this->slug}/anmeldung/verwalten/".str_repeat('b', 48));
    }

    $this->get($this->url)->assertTooManyRequests();
});
