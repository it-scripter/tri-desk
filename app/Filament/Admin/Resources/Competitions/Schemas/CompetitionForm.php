<?php

namespace App\Filament\Admin\Resources\Competitions\Schemas;

use App\Domain\Competition\Rules\NotReservedSlug;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CompetitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('competition.label'))->schema([
                TextInput::make('name')
                    ->label(__('competition.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (?string $state, callable $set, string $operation) => $operation === 'create'
                        ? $set('slug', Str::slug((string) $state))
                        : null),

                TextInput::make('slug')
                    ->label(__('competition.fields.slug'))
                    ->helperText(__('competition.hints.slug'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rules(['alpha_dash', new NotReservedSlug]),

                TextInput::make('domain')
                    ->label(__('competition.fields.domain'))
                    ->helperText(__('competition.hints.domain'))
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Toggle::make('is_active')
                    ->label(__('competition.fields.is_active'))
                    ->default(true),

                TextInput::make('sort')
                    ->label(__('competition.fields.sort'))
                    ->helperText(__('competition.hints.sort'))
                    ->numeric()
                    ->default(0),
            ])->columns(2),
        ]);
    }
}
