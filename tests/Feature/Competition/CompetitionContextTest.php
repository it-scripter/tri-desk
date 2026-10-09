<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\Race;
use App\Support\CompetitionContext;

/**
 * C-01: every competition has its own start page as a sub path of the portal
 * domain, the context comes from the path through middleware, and links are
 * only ever built from named routes.
 */
it('resolves the competition from the path', function () {
    $competition = Competition::factory()->create(['name' => 'Volkstriathlon', 'slug' => 'volkstriathlon']);

    $this->get('/volkstriathlon')
        ->assertSuccessful()
        ->assertSee('Volkstriathlon');

    expect(app(CompetitionContext::class)->competition()?->is($competition))->toBeTrue();
});

it('answers 404 for an unknown competition', function () {
    $this->get('/gibtsnicht')->assertNotFound();
});

it('answers 404 for an inactive competition', function () {
    Competition::factory()->inactive()->create(['slug' => 'pausiert']);

    $this->get('/pausiert')->assertNotFound();
});

it('does not let a competition route swallow a panel path', function () {
    // Even if someone managed to store a competition under a reserved slug,
    // the route pattern keeps /admin at the panel.
    Competition::factory()->create(['slug' => 'admin']);

    $this->get('/admin')->assertRedirect('/admin/login');
});

it('builds competition links without writing the path by hand', function () {
    $competition = Competition::factory()->create(['slug' => 'berlinwomen']);

    expect(route('competition.home', $competition))->toEndWith('/berlinwomen');
});

it('fills the competition parameter of named routes inside the context', function () {
    Competition::factory()->create(['slug' => 'volkstriathlon']);

    // Inside the context the parameter is implied, so views and controllers
    // never need to pass it (URL::defaults in the middleware).
    $this->get('/volkstriathlon')->assertSuccessful();

    expect(route('competition.home'))->toEndWith('/volkstriathlon');
});

it('exposes the current edition and its races on the competition page', function () {
    $competition = Competition::factory()->create(['name' => 'Volkstriathlon', 'slug' => 'volkstriathlon']);

    $edition = Edition::factory()->for($competition)->current()->create([
        'year' => 2027,
        'number' => 38,
        'slug' => '2027',
        'starts_on' => '2027-06-27',
    ]);

    Race::factory()->for($edition)->create(['name' => 'Volxtri4kids']);

    $this->get('/volkstriathlon')
        ->assertSuccessful()
        ->assertSee('38. Volkstriathlon 2027')
        ->assertSee('Volxtri4kids')
        ->assertSee('Schwimmen – Rad – Lauf', escape: false);
});

it('shows a notice when a competition has no edition yet', function () {
    Competition::factory()->create(['slug' => 'neu']);

    $this->get('/neu')
        ->assertSuccessful()
        ->assertSee('noch keine Ausgabe');
});

it('lists the competitions on the portal start page', function () {
    Competition::factory()->create(['name' => 'Volkstriathlon', 'slug' => 'volkstriathlon']);
    Competition::factory()->create(['name' => 'BerlinWoMen', 'slug' => 'berlinwomen']);
    Competition::factory()->inactive()->create(['name' => 'Eingestellt', 'slug' => 'eingestellt']);

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Volkstriathlon')
        ->assertSee('BerlinWoMen')
        ->assertDontSee('Eingestellt');
});

it('prefers the host over the path once a competition has its own domain', function () {
    Competition::factory()->create(['slug' => 'volkstriathlon']);
    $owned = Competition::factory()->create(['slug' => 'berlinwomen', 'domain' => 'berlinwomen.test']);

    // Prepares the later switch to own domains: the resolver checks the host
    // first, so the path segment no longer decides.
    $this->get('http://berlinwomen.test/volkstriathlon')->assertSuccessful();

    expect(app(CompetitionContext::class)->competition()?->is($owned))->toBeTrue();
});
