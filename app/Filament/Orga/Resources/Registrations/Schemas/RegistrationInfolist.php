<?php

namespace App\Filament\Orga\Resources\Registrations\Schemas;

use App\Domain\Competition\Enums\Gender;
use App\Domain\Competition\Enums\RegistrationStatus;
use App\Domain\Competition\Enums\TeamClassification;
use App\Domain\Competition\Models\RegistrationParticipant;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RegistrationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('registration.sections.entry'))->schema([
                TextEntry::make('race.name')
                    ->label(__('registration.fields.race')),

                TextEntry::make('status')
                    ->label(__('registration.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (RegistrationStatus $state): string => $state->label()),

                TextEntry::make('priceTier.name')
                    ->label(__('registration.fields.price_tier'))
                    ->placeholder('-'),

                TextEntry::make('amount_cents')
                    ->label(__('registration.fields.amount'))
                    ->money('EUR', divideBy: 100),

                TextEntry::make('reserved_until')
                    ->label(__('registration.fields.reserved_until'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-'),

                TextEntry::make('confirmed_at')
                    ->label(__('registration.fields.confirmed_at'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-'),

                TextEntry::make('cancelled_at')
                    ->label(__('registration.fields.cancelled_at'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-'),

                TextEntry::make('team_name')
                    ->label(__('registration.fields.team_name'))
                    ->placeholder('-'),

                TextEntry::make('team_classification')
                    ->label(__('registration.fields.team_classification'))
                    ->formatStateUsing(fn (?TeamClassification $state): string => $state?->label() ?? '-')
                    ->placeholder('-'),

                IconEntry::make('entered_by_orga')
                    ->label(__('registration.fields.entered_by_orga'))
                    ->boolean(),

                IconEntry::make('is_test')
                    ->label(__('registration.fields.is_test'))
                    ->boolean(),

                TextEntry::make('created_at')
                    ->label(__('registration.fields.created_at'))
                    ->dateTime('d.m.Y H:i'),
            ])->columns(3),

            Section::make(__('registration.sections.contact'))->schema([
                TextEntry::make('contact_first_name')
                    ->label(__('registration.fields.contact'))
                    ->formatStateUsing(fn (string $state, $record): string => "{$state} {$record->contact_last_name}"),

                TextEntry::make('contact_email')
                    ->label(__('registration.fields.contact_email')),
            ])->columns(2),

            Section::make(__('registration.sections.participants'))->schema([
                RepeatableEntry::make('participants')
                    ->hiddenLabel()
                    ->schema([
                        TextEntry::make('first_name')
                            ->label(__('registration.fields.name'))
                            ->state(fn (RegistrationParticipant $record): string => $record->fullName()),

                        TextEntry::make('birth_date')
                            ->label(__('registration.fields.birth_date'))
                            ->date('d.m.Y'),

                        TextEntry::make('gender')
                            ->label(__('registration.fields.gender'))
                            ->formatStateUsing(fn (Gender $state): string => $state->label()),

                        TextEntry::make('scored_as')
                            ->label(__('registration.fields.scored_as'))
                            ->formatStateUsing(fn (?Gender $state): string => $state?->label() ?? '-')
                            ->placeholder('-'),

                        TextEntry::make('ageGroup.code')
                            ->label(__('registration.fields.age_group'))
                            ->placeholder('-'),

                        TextEntry::make('guardian_name')
                            ->label(__('registration.fields.guardian'))
                            ->placeholder('-'),
                    ])
                    ->columns(6),
            ]),

            Section::make(__('registration.sections.options'))->schema([
                RepeatableEntry::make('options')
                    ->hiddenLabel()
                    ->schema([
                        TextEntry::make('option.name')
                            ->label(__('registration.fields.option')),

                        TextEntry::make('variant.name')
                            ->label(__('registration.fields.variant'))
                            ->placeholder('-'),

                        TextEntry::make('price_cents')
                            ->label(__('registration.fields.price'))
                            ->money('EUR', divideBy: 100),
                    ])
                    ->columns(3),
            ])->visible(fn ($record): bool => $record->options->isNotEmpty()),
        ]);
    }
}
