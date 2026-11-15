<?php

namespace App\Filament\Orga\Resources\Editions\Actions;

use App\Domain\Competition\Models\Edition;
use App\Http\Registration\RegistrationTestMode;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/**
 * Opens the public registration form in the test mode for this edition, at a
 * chosen moment - prefilled with the registration start, or now where that
 * has passed. Test registrations are stored and mailed, but marked and never
 * hold a place.
 */
class TestRegistrationAction
{
    public static function make(): Action
    {
        return Action::make('testRegistration')
            ->label(__('registration.test.action'))
            ->icon(Heroicon::OutlinedBeaker)
            ->authorize(fn (Edition $record): bool => Filament::auth()->user()?->can('testRegistration', $record) ?? false)
            ->modalHeading(__('registration.test.heading'))
            ->modalDescription(__('registration.test.description'))
            ->fillForm(fn (Edition $record): array => [
                'at' => ($record->registration_opens_at?->isFuture() ? $record->registration_opens_at : now())
                    ->format('Y-m-d H:i'),
            ])
            ->schema([
                DateTimePicker::make('at')
                    ->label(__('registration.test.at'))
                    ->helperText(__('registration.test.at_hint'))
                    ->seconds(false)
                    ->required(),
            ])
            ->action(function (Edition $record, array $data, Action $action): void {
                $user = Filament::auth()->user();

                if (! $user instanceof User) {
                    return;
                }

                RegistrationTestMode::start(session()->driver(), $record, Carbon::parse($data['at']), $user);

                // The public pages take the competition from the path; the
                // panel has no competition context, so the slug is given.
                $action->redirect(route('competition.registration.race', [
                    'competition' => $record->competition->slug,
                ]));
            });
    }
}
