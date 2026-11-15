<?php

namespace App\Filament\Orga\Resources\Editions\Actions;

use App\Domain\Competition\Models\Edition;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\URL;

/**
 * Opens the public registration form in the test mode for this edition, in
 * a new tab, so the panel stays where it was. The moment under test starts
 * at the registration start (or now, where that has passed) and can be moved
 * in the banner of the form. Test registrations are stored and mailed, but
 * marked and never hold a place.
 */
class TestRegistrationAction
{
    /**
     * The start link is signed so no link from elsewhere can switch a signed
     * in person into the test mode; long enough for a panel page left open.
     */
    private const LINK_VALID_HOURS = 12;

    public static function make(): Action
    {
        return Action::make('testRegistration')
            ->label(__('registration.test.action'))
            ->tooltip(__('registration.test.description'))
            ->icon(Heroicon::OutlinedBeaker)
            ->authorize(fn (Edition $record): bool => Filament::auth()->user()?->can('testRegistration', $record) ?? false)
            // The panel has no competition context, so the slug is given.
            ->url(fn (Edition $record): string => URL::temporarySignedRoute(
                'competition.registration.test.start',
                now()->addHours(self::LINK_VALID_HOURS),
                ['competition' => $record->competition->slug, 'edition' => $record->id],
            ), shouldOpenInNewTab: true);
    }
}
