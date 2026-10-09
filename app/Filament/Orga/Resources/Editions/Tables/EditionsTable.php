<?php

namespace App\Filament\Orga\Resources\Editions\Tables;

use App\Domain\Competition\Enums\EditionStatus;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EditionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('competition.name')
                    ->label(__('edition.fields.competition'))
                    ->sortable(),

                TextColumn::make('year')
                    ->label(__('edition.fields.year'))
                    ->sortable(),

                TextColumn::make('number')
                    ->label(__('edition.fields.number'))
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label(__('edition.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (EditionStatus $state): string => $state->label()),

                TextColumn::make('starts_on')
                    ->label(__('edition.fields.starts_on'))
                    ->date('d.m.Y')
                    ->placeholder('-'),

                TextColumn::make('races_count')
                    ->label(__('edition.fields.races_count'))
                    ->counts('races'),

                IconColumn::make('is_current')
                    ->label(__('edition.fields.is_current'))
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
