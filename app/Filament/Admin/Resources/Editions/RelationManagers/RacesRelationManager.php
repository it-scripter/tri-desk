<?php

namespace App\Filament\Admin\Resources\Editions\RelationManagers;

use App\Domain\Competition\Enums\RaceType;
use App\Domain\Competition\Models\Race;
use App\Filament\Shared\RaceSchema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RacesRelationManager extends RelationManager
{
    protected static string $relationship = 'races';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('race.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        // The edition is fixed here: it is the record the relation manager
        // hangs off, so the form shows no edition select.
        return $schema->components(RaceSchema::fields(
            editionId: fn (): mixed => $this->getOwnerRecord()->getKey(),
        ));
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort')
            ->modelLabel(__('race.label'))
            ->pluralModelLabel(__('race.plural_label'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('race.fields.name'))
                    ->searchable()
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
            ->headerActions([
                CreateAction::make(),
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
