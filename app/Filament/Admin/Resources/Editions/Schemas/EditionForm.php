<?php

namespace App\Filament\Admin\Resources\Editions\Schemas;

use App\Domain\Competition\Enums\EditionStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('edition.sections.basics'))->schema([
                Select::make('competition_id')
                    ->label(__('edition.fields.competition'))
                    ->relationship('competition', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                TextInput::make('year')
                    ->label(__('edition.fields.year'))
                    ->required()
                    ->numeric()
                    ->minValue(1980)
                    ->maxValue(2100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (?string $state, callable $set, string $operation) => $operation === 'create'
                        ? $set('slug', (string) $state)
                        : null),

                TextInput::make('slug')
                    ->label(__('edition.fields.slug'))
                    ->required()
                    ->maxLength(255)
                    ->rules(['alpha_dash'])
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, callable $get) => $rule->where('competition_id', $get('competition_id'))),

                TextInput::make('number')
                    ->label(__('edition.fields.number'))
                    ->helperText(__('edition.hints.number'))
                    ->numeric()
                    ->minValue(1),

                TextInput::make('display_name')
                    ->label(__('edition.fields.display_name'))
                    ->helperText(__('edition.hints.display_name'))
                    ->maxLength(255),

                Select::make('status')
                    ->label(__('edition.fields.status'))
                    ->options(EditionStatus::labels())
                    ->default(EditionStatus::Planned->value)
                    ->required(),

                Toggle::make('is_current')
                    ->label(__('edition.fields.is_current'))
                    ->helperText(__('edition.hints.is_current')),
            ])->columns(2),

            Section::make(__('edition.sections.schedule'))->schema([
                DatePicker::make('starts_on')
                    ->label(__('edition.fields.starts_on'))
                    ->displayFormat('d.m.Y'),

                DatePicker::make('ends_on')
                    ->label(__('edition.fields.ends_on'))
                    ->displayFormat('d.m.Y')
                    ->afterOrEqual('starts_on'),

                DateTimePicker::make('registration_opens_at')
                    ->label(__('edition.fields.registration_opens_at'))
                    ->displayFormat('d.m.Y H:i')
                    ->seconds(false),

                DateTimePicker::make('registration_closes_at')
                    ->label(__('edition.fields.registration_closes_at'))
                    ->displayFormat('d.m.Y H:i')
                    ->seconds(false)
                    ->afterOrEqual('registration_opens_at'),

                DateTimePicker::make('transfer_deadline_at')
                    ->label(__('edition.fields.transfer_deadline_at'))
                    ->displayFormat('d.m.Y H:i')
                    ->seconds(false),

                TextInput::make('payment_window_hours')
                    ->label(__('edition.fields.payment_window_hours'))
                    ->helperText(__('edition.hints.payment_window_hours'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(720)
                    ->suffix('h')
                    ->default(48)
                    ->required(),

                Textarea::make('note')
                    ->label(__('edition.fields.note'))
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
