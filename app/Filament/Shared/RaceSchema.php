<?php

namespace App\Filament\Shared;

use App\Domain\Competition\Enums\Discipline;
use App\Domain\Competition\Enums\EditionStatus;
use App\Domain\Competition\Enums\RaceType;
use App\Domain\Competition\Enums\TeamClassification;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Rules\EditableEdition;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Form fields of a race, shared by the relation manager in the admin panel and
 * the resource in the orga panel - one definition, so both stay in step.
 */
class RaceSchema
{
    /**
     * @param  Closure|null  $editionId  Resolves the edition when it is fixed by
     *                                   the context, as in a relation manager.
     *                                   Without it the form offers a select.
     * @return array<int, Section>
     */
    public static function fields(?Closure $editionId = null): array
    {
        $resolveEdition = $editionId !== null
            ? fn (Get $get): mixed => ($editionId)()
            : fn (Get $get): mixed => $get('edition_id');

        return [
            Section::make(__('race.sections.basics'))->schema([
                ...($editionId === null ? [
                    Select::make('edition_id')
                        ->label(__('race.fields.edition'))
                        ->relationship(
                            'edition',
                            'slug',
                            // Closed editions are read only, so they are not
                            // offered - and the rule below refuses them even
                            // if one is sent anyway.
                            fn (Builder $query): Builder => $query->whereNotIn('status', [
                                EditionStatus::Archived->value,
                                EditionStatus::Cancelled->value,
                            ]),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Edition $record): string => $record->titleWithNumber())
                        ->required()
                        ->rules([new EditableEdition])
                        ->searchable()
                        ->preload()
                        ->live(),
                ] : []),

                TextInput::make('name')
                    ->label(__('race.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (?string $state, callable $set, string $operation) => $operation === 'create'
                        ? $set('slug', Str::slug((string) $state))
                        : null),

                TextInput::make('slug')
                    ->label(__('race.fields.slug'))
                    ->required()
                    ->maxLength(255)
                    ->rules(['alpha_dash']),

                Select::make('edition_day_id')
                    ->label(__('race.fields.edition_day'))
                    ->helperText(__('race.hints.edition_day'))
                    ->options(fn (Get $get): array => self::dayOptions($resolveEdition($get)))
                    ->searchable(),

                TextInput::make('sort')
                    ->label(__('race.fields.sort'))
                    ->numeric()
                    ->default(0),
            ])->columns(2),

            Section::make(__('race.sections.format'))->schema([
                Select::make('type')
                    ->label(__('race.fields.type'))
                    ->options(RaceType::labels())
                    ->default(RaceType::Individual->value)
                    ->required()
                    ->live(),

                TextInput::make('team_size_min')
                    ->label(__('race.fields.team_size_min'))
                    ->helperText(__('race.hints.team_size'))
                    ->numeric()
                    ->minValue(2)
                    ->maxValue(20)
                    ->visible(fn (Get $get): bool => self::isTeamType($get('type'))),

                TextInput::make('team_size_max')
                    ->label(__('race.fields.team_size_max'))
                    ->numeric()
                    ->minValue(2)
                    ->maxValue(20)
                    ->gte('team_size_min')
                    ->visible(fn (Get $get): bool => self::isTeamType($get('type'))),

                TimePicker::make('starts_at')
                    ->label(__('race.fields.starts_at'))
                    ->seconds(false),

                // An ordered sequence, so a repeater with drag handles rather
                // than a multi select: the order of swim, bike and run drives
                // transition zones, splits and result lists (W-02). simple()
                // keeps the state a flat list of values, which is exactly what
                // the AsEnumCollection cast expects.
                Repeater::make('discipline_sequence')
                    ->label(__('race.fields.discipline_sequence'))
                    ->helperText(__('race.hints.discipline_sequence'))
                    ->simple(Select::make('discipline')
                        ->hiddenLabel()
                        ->options(Discipline::labels())
                        ->required())
                    ->reorderable()
                    ->minItems(1)
                    ->default([Discipline::Swim->value, Discipline::Bike->value, Discipline::Run->value])
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make(__('race.sections.limits'))->schema([
                TextInput::make('participant_limit')
                    ->label(__('race.fields.participant_limit'))
                    ->helperText(__('race.hints.participant_limit'))
                    ->numeric()
                    ->minValue(1),

                TextInput::make('min_age')
                    ->label(__('race.fields.min_age'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(120),

                TextInput::make('max_age')
                    ->label(__('race.fields.max_age'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(120)
                    ->gte('min_age'),

                Toggle::make('is_public')
                    ->label(__('race.fields.is_public'))
                    ->default(true),
            ])->columns(2),

            // Scoring (W-05): the age group scheme, whether "d" may be
            // entered, and the classifications of relays and teams. How a
            // team falls into one of them is settled with the registration.
            Section::make(__('race.sections.scoring'))->schema([
                Select::make('age_group_scheme_id')
                    ->label(__('race.fields.age_group_scheme'))
                    ->relationship('ageGroupScheme', 'name')
                    ->preload(),

                Toggle::make('allows_diverse')
                    ->label(__('race.fields.allows_diverse'))
                    ->helperText(__('race.hints.allows_diverse')),

                CheckboxList::make('team_classifications')
                    ->label(__('race.fields.team_classifications'))
                    ->helperText(__('race.hints.team_classifications'))
                    ->options(TeamClassification::labels())
                    ->columns(4)
                    ->visible(fn (Get $get): bool => self::isTeamType($get('type')))
                    ->columnSpanFull(),

                TextInput::make('mixed_min_male')
                    ->label(__('race.fields.mixed_min_male'))
                    ->helperText(__('race.hints.mixed_min'))
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required()
                    ->visible(fn (Get $get): bool => self::isTeamType($get('type'))),

                TextInput::make('mixed_min_female')
                    ->label(__('race.fields.mixed_min_female'))
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required()
                    ->visible(fn (Get $get): bool => self::isTeamType($get('type'))),
            ])->columns(2),
        ];
    }

    private static function isTeamType(mixed $type): bool
    {
        $type = $type instanceof RaceType ? $type : RaceType::tryFrom(is_string($type) ? $type : '');

        return in_array($type, [RaceType::Relay, RaceType::Team], true);
    }

    /**
     * Race days of the edition, for the day select. Empty while no edition is
     * chosen - a single day edition simply has no day to pick.
     *
     * @return array<int, string>
     */
    public static function dayOptions(mixed $editionId): array
    {
        if (! is_numeric($editionId)) {
            return [];
        }

        $edition = Edition::query()->with('days')->find($editionId);

        return $edition?->days
            ->mapWithKeys(fn (object $day): array => [$day->id => $day->title()])
            ->all() ?? [];
    }
}
