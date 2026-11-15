<?php

namespace App\Filament\Shared\RelationManagers;

use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Rules\NonOverlappingPriceTier;
use App\Filament\Shared\MoneyInput;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Price tiers of a race by date (specification W-03). The periods of a race
 * must not overlap, so the price at any moment is unambiguous.
 */
class PriceTiersRelationManager extends RelationManager
{
    protected static string $relationship = 'priceTiers';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('price.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('price.fields.name'))
                ->required()
                ->maxLength(255),

            MoneyInput::make('amount_cents')
                ->label(__('price.fields.amount'))
                ->helperText(__('price.hints.amount'))
                ->required(),

            DateTimePicker::make('valid_from')
                ->label(__('price.fields.valid_from'))
                ->helperText(__('price.hints.valid_from'))
                ->seconds(false)
                ->rules(fn (Get $get, ?PriceTier $record): array => [
                    new NonOverlappingPriceTier(
                        (int) $this->getOwnerRecord()->getKey(),
                        $get('valid_until'),
                        $record?->getKey(),
                    ),
                ]),

            DateTimePicker::make('valid_until')
                ->label(__('price.fields.valid_until'))
                ->helperText(__('price.hints.valid_until'))
                ->seconds(false)
                ->after('valid_from'),

            Toggle::make('orga_only')
                ->label(__('price.fields.orga_only'))
                ->helperText(__('price.hints.orga_only'))
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('valid_from')
            ->modelLabel(__('price.label'))
            ->pluralModelLabel(__('price.plural_label'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('price.fields.name')),

                TextColumn::make('amount_cents')
                    ->label(__('price.fields.amount'))
                    ->money('EUR', divideBy: 100),

                TextColumn::make('valid_from')
                    ->label(__('price.fields.valid_from'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-'),

                TextColumn::make('valid_until')
                    ->label(__('price.fields.valid_until'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-'),

                IconColumn::make('orga_only')
                    ->label(__('price.fields.orga_only'))
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
