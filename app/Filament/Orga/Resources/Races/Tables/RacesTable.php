<?php

namespace App\Filament\Orga\Resources\Races\Tables;

use App\Domain\Competition\Enums\RaceType;
use App\Domain\Competition\Models\Race;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RacesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')
                    ->label(__('race.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('edition.year')
                    ->label(__('race.fields.edition'))
                    ->state(fn (Race $record): string => $record->edition->titleWithNumber())
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('race.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (RaceType $state): string => $state->label()),

                TextColumn::make('discipline_sequence')
                    ->label(__('race.fields.discipline_sequence'))
                    ->state(fn (Race $record): string => $record->disciplineSequenceLabel()),

                TextColumn::make('editionDay.date')
                    ->label(__('race.fields.edition_day'))
                    ->state(fn (Race $record): string => $record->editionDay?->title() ?? '-'),

                TextColumn::make('participant_limit')
                    ->label(__('race.fields.participant_limit'))
                    ->placeholder('-'),

                IconColumn::make('is_public')
                    ->label(__('race.fields.is_public'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('race.fields.type'))
                    ->options(RaceType::labels()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
