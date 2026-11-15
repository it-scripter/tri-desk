<?php

namespace App\Filament\Admin\Resources\AgeGroupSchemes\RelationManagers;

use App\Domain\Competition\Enums\Gender;
use App\Domain\Competition\Models\AgeGroup;
use App\Domain\Competition\Models\AgeGroupScheme;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

/**
 * Age groups of a scheme (specification W-05). The first group in order that
 * fits age and gender is the one an entry is placed in.
 */
class AgeGroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'ageGroups';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('age_group.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label(__('age_group.fields.code'))
                ->required()
                ->maxLength(255)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('age_group_scheme_id', $this->getOwnerRecord()->getKey()),
                ),

            TextInput::make('name')
                ->label(__('age_group.fields.name'))
                ->maxLength(255),

            Select::make('gender')
                ->label(__('age_group.fields.gender'))
                ->helperText(__('age_group.hints.gender'))
                ->options(Gender::labels()),

            TextInput::make('sort')
                ->label(__('age_group.fields.sort'))
                ->numeric()
                ->default(function (): int {
                    $scheme = $this->getOwnerRecord();

                    return $scheme instanceof AgeGroupScheme ? $scheme->ageGroups()->count() : 0;
                }),

            TextInput::make('min_age')
                ->label(__('age_group.fields.min_age'))
                ->helperText(__('age_group.hints.age'))
                ->numeric()
                ->minValue(0)
                ->maxValue(120),

            TextInput::make('max_age')
                ->label(__('age_group.fields.max_age'))
                ->numeric()
                ->minValue(0)
                ->maxValue(120)
                ->gte('min_age'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->modelLabel(__('age_group.label'))
            ->pluralModelLabel(__('age_group.plural_label'))
            ->reorderable('sort')
            ->columns([
                TextColumn::make('code')
                    ->label(__('age_group.fields.code')),

                TextColumn::make('name')
                    ->label(__('age_group.fields.name'))
                    ->placeholder('-'),

                TextColumn::make('gender')
                    ->label(__('age_group.fields.gender'))
                    ->state(fn (AgeGroup $record): string => $record->gender?->label() ?? __('age_group.all_genders')),

                TextColumn::make('min_age')
                    ->label(__('age_group.fields.min_age'))
                    ->placeholder('-'),

                TextColumn::make('max_age')
                    ->label(__('age_group.fields.max_age'))
                    ->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
