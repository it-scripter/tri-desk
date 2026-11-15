<?php

use App\Domain\Competition\Models\Competition;
use App\Http\Controllers\ManageRegistrationController;
use App\Http\Controllers\RegistrationFormController;
use App\Http\Controllers\RegistrationTestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal
|--------------------------------------------------------------------------
|
| The organiser's own start page. Teasers per competition are fed from the
| competition data later (specification C-03).
|
*/

Route::view('/', 'portal.home')->name('portal.home');

/*
|--------------------------------------------------------------------------
| Competition pages
|--------------------------------------------------------------------------
|
| Every competition lives under its own path segment (C-01). The middleware
| puts the competition into the context and teaches named routes to fill the
| parameter themselves, so no path is ever written by hand.
|
| The route pattern excludes the reserved slugs, so the panel paths /admin,
| /orga and /team can never be swallowed by this group.
|
*/

Route::prefix('{competition}')
    ->middleware('competition')
    ->name('competition.')
    ->where(['competition' => Competition::slugRoutePattern()])
    ->group(function (): void {
        Route::view('/', 'competition.home')->name('home');

        // Registration form (W-10). Every step is a plain form posted to the
        // server; the draft waits in the session until it is submitted.
        Route::controller(RegistrationFormController::class)
            ->prefix('anmeldung')
            ->name('registration.')
            ->middleware('registration.test')
            ->group(function (): void {
                Route::get('/', 'race')->name('race');
                Route::post('/', 'chooseRace')->name('race.store');
                Route::get('personen', 'people')->name('people');
                Route::post('personen', 'storePeople')->name('people.store');
                Route::get('optionen', 'options')->name('options');
                Route::post('optionen', 'storeOptions')->name('options.store');
                Route::get('zustimmungen', 'consents')->name('consents');
                Route::post('zustimmungen', 'storeConsents')->name('consents.store');
                Route::get('pruefen', 'summary')->name('summary');
                Route::post('pruefen', 'submit')->middleware('throttle:registration')->name('submit');
                Route::get('eingegangen', 'done')->name('done');
            });

        // Test mode of the form, started from the orga panel in a new tab.
        Route::controller(RegistrationTestController::class)
            ->prefix('anmeldung/test')
            ->name('registration.test.')
            ->middleware('registration.test')
            ->group(function (): void {
                Route::get('starten/{edition}', 'start')->middleware('signed')->whereNumber('edition')->name('start');
                Route::post('zeitpunkt', 'move')->name('move');
                Route::post('beenden', 'end')->name('end');
            });

        // Magic link of the confirmation mail (W-15).
        Route::controller(ManageRegistrationController::class)
            ->prefix('anmeldung/verwalten/{token}')
            ->name('registration.manage')
            ->middleware('throttle:magic-link')
            ->where(['token' => '[A-Za-z0-9]{48}'])
            ->group(function (): void {
                Route::get('/', 'show');
                Route::post('stornieren', 'cancel')->name('.cancel');
            });
    });
