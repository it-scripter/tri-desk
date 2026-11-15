<?php

namespace App\Filament\Shared\RelationManagers;

use App\Filament\Shared\MoneyInput;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

/**
 * Cancellation rules of an edition as steps (specification W-04). Shared by
 * the admin and the orga panel.
 */
class CancellationRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'cancellationRules';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('cancellation.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('valid_until')
                ->label(__('cancellation.fields.valid_until'))
                ->helperText(__('cancellation.hints.valid_until'))
                ->seconds(false)
                ->required()
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('edition_id', $this->getOwnerRecord()->getKey()),
                ),

            TextInput::make('refund_percent')
                ->label(__('cancellation.fields.refund_percent'))
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%')
                ->required(),

            MoneyInput::make('fee_cents')
                ->label(__('cancellation.fields.fee'))
                ->helperText(__('cancellation.hints.fee'))
                ->default(0)
                ->required(),

            TextInput::make('note')
                ->label(__('cancellation.fields.note'))
                ->maxLength(255),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('valid_until')
            ->modelLabel(__('cancellation.label'))
            ->pluralModelLabel(__('cancellation.plural_label'))
            ->columns([
                TextColumn::make('valid_until')
                    ->label(__('cancellation.fields.valid_until'))
                    ->dateTime('d.m.Y H:i'),

                TextColumn::make('refund_percent')
                    ->label(__('cancellation.fields.refund_percent'))
                    ->suffix(' %'),

                TextColumn::make('fee_cents')
                    ->label(__('cancellation.fields.fee'))
                    ->money('EUR', divideBy: 100),

                TextColumn::make('note')
                    ->label(__('cancellation.fields.note'))
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
