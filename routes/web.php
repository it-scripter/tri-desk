<?php

use App\Domain\Competition\Models\Competition;
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
    });
