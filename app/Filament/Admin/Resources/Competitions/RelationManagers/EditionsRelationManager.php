<?php

namespace App\Filament\Admin\Resources\Competitions\RelationManagers;

use App\Domain\Competition\Enums\EditionStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Editions of a competition, read only here. They are created and edited in
 * the edition resource, which has the race days and races attached to it.
 */
class EditionsRelationManager extends RelationManager
{
    protected static string $relationship = 'editions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('edition.plural_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->modelLabel(__('edition.label'))
            ->pluralModelLabel(__('edition.plural_label'))
            ->columns([
                TextColumn::make('year')
                    ->label(__('edition.fields.year'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('number')
                    ->label(__('edition.fields.number'))
                    ->placeholder('-'),

                TextColumn::make('display_name')
                    ->label(__('edition.fields.display_name'))
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label(__('edition.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (EditionStatus $state): string => $state->label()),

                TextColumn::make('races_count')
                    ->label(__('edition.fields.races_count'))
                    ->counts('races'),

                IconColumn::make('is_current')
                    ->label(__('edition.fields.is_current'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('edition.fields.status'))
                    ->options(EditionStatus::labels()),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
