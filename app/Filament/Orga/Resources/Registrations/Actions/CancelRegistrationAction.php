<?php

namespace App\Filament\Orga\Resources\Registrations\Actions;

use App\Domain\Competition\Enums\RegistrationStatus;
use App\Domain\Competition\Models\Registration;
use App\Domain\Competition\Services\RegistrationService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Cancels a registration and frees its place. The policy decides; the button
 * is only hidden where there is nothing left to cancel.
 */
class CancelRegistrationAction
{
    public static function make(): Action
    {
        return Action::make('cancel')
            ->label(__('registration.actions.cancel'))
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription(__('registration.actions.cancel_confirm'))
            ->visible(fn (Registration $record): bool => in_array($record->status, [RegistrationStatus::Reserved, RegistrationStatus::Confirmed], true))
            ->authorize(fn (Registration $record): bool => Filament::auth()->user()?->can('cancel', $record) ?? false)
            ->action(function (Registration $record): void {
                app(RegistrationService::class)->cancel($record);

                Notification::make()
                    ->success()
                    ->title(__('registration.actions.cancelled'))
                    ->send();
            });
    }
}
