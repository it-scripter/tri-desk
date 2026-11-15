<?php

namespace App\Filament\Shared\RelationManagers;

use App\Domain\Competition\Models\RaceOption;
use App\Filament\Shared\MoneyInput;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Additional options of an edition (specification W-03): shirt, pasta party,
 * chip rental, with variants such as shirt sizes. Shared by the admin and the
 * orga panel.
 */
class RaceOptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'raceOptions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('option.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('option.fields.name'))
                ->required()
                ->maxLength(255),

            MoneyInput::make('price_cents')
                ->label(__('option.fields.price'))
                ->required()
                ->default(0),

            Textarea::make('description')
                ->label(__('option.fields.description'))
                ->columnSpanFull(),

            TextInput::make('quota')
                ->label(__('option.fields.quota'))
                ->helperText(__('option.hints.quota'))
                ->numeric()
                ->minValue(1),

            TextInput::make('sort')
                ->label(__('option.fields.sort'))
                ->numeric()
                ->default(0),

            // Only races of this edition are offered - the option belongs to
            // the edition the relation manager hangs off.
            Select::make('races')
                ->label(__('option.fields.races'))
                ->helperText(__('option.hints.races'))
                ->relationship(
                    'races',
                    'name',
                    fn (Builder $query): Builder => $query->where('edition_id', $this->getOwnerRecord()->getKey()),
                )
                ->multiple()
                ->preload()
                ->columnSpanFull(),

            Repeater::make('variants')
                ->label(__('option.fields.variants'))
                ->helperText(__('option.hints.variants'))
                ->relationship()
                ->orderColumn('sort')
                ->schema([
                    TextInput::make('name')
                        ->label(__('option.fields.variant_name'))
                        ->required()
                        ->maxLength(255),

                    TextInput::make('quota')
                        ->label(__('option.fields.variant_quota'))
                        ->numeric()
                        ->minValue(1),
                ])
                ->columns(2)
                ->defaultItems(0)
                ->columnSpanFull(),

            Toggle::make('is_active')
                ->label(__('option.fields.is_active'))
                ->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->modelLabel(__('option.label'))
            ->pluralModelLabel(__('option.plural_label'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('option.fields.name')),

                TextColumn::make('price_cents')
                    ->label(__('option.fields.price'))
                    ->money('EUR', divideBy: 100),

                TextColumn::make('races.name')
                    ->label(__('option.fields.races'))
                    ->state(fn (RaceOption $record): string => $record->races->isEmpty()
                        ? __('option.all_races')
                        : $record->races->pluck('name')->implode(', ')),

                TextColumn::make('variants_count')
                    ->label(__('option.fields.variants'))
                    ->counts('variants'),

                TextColumn::make('quota')
                    ->label(__('option.fields.quota'))
                    ->placeholder('-'),

                IconColumn::make('is_active')
                    ->label(__('option.fields.is_active'))
                    ->boolean(),
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
