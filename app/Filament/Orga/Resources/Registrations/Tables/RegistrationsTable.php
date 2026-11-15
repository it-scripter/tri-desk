<?php

namespace App\Filament\Orga\Resources\Registrations\Tables;

use App\Domain\Competition\Enums\RegistrationStatus;
use App\Domain\Competition\Enums\TeamClassification;
use App\Domain\Competition\Models\Registration;
use App\Domain\Competition\Models\RegistrationParticipant;
use App\Filament\Orga\Resources\Registrations\Actions\CancelRegistrationAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('participants.last_name')
                    ->label(__('registration.fields.participants'))
                    ->state(fn (Registration $record): string => $record->team_name
                        ?? $record->participants->map(fn (RegistrationParticipant $p): string => $p->fullName())->implode(', '))
                    ->description(fn (Registration $record): ?string => $record->team_name !== null
                        ? $record->participants->map(fn (RegistrationParticipant $p): string => $p->fullName())->implode(', ')
                        : null)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(fn (Builder $q) => $q
                        ->where('team_name', 'like', "%{$search}%")
                        ->orWhere('contact_email', 'like', "%{$search}%")
                        ->orWhereHas('participants', fn (Builder $p) => $p
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")))),

                TextColumn::make('race.name')
                    ->label(__('registration.fields.race'))
                    ->sortable(),

                TextColumn::make('team_classification')
                    ->label(__('registration.fields.team_classification'))
                    ->formatStateUsing(fn (?TeamClassification $state): string => $state?->label() ?? '-')
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label(__('registration.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (RegistrationStatus $state): string => $state->label())
                    ->color(fn (RegistrationStatus $state): string => match ($state) {
                        RegistrationStatus::Confirmed => 'success',
                        RegistrationStatus::Reserved => 'warning',
                        RegistrationStatus::Cancelled, RegistrationStatus::Expired => 'gray',
                    }),

                TextColumn::make('amount_cents')
                    ->label(__('registration.fields.amount'))
                    ->money('EUR', divideBy: 100)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('registration.fields.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('race')
                    ->label(__('registration.fields.race'))
                    ->relationship('race', 'name')
                    ->preload(),

                SelectFilter::make('status')
                    ->label(__('registration.fields.status'))
                    ->options(RegistrationStatus::labels()),
            ])
            ->recordActions([
                ViewAction::make(),
                CancelRegistrationAction::make(),
            ]);
    }
}
