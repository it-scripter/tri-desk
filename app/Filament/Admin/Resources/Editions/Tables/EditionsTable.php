<?php

namespace App\Filament\Admin\Resources\Editions\Tables;

use App\Domain\Competition\Enums\EditionStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                    ->sortable()
                    ->searchable(),

                TextColumn::make('year')
                    ->label(__('edition.fields.year'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('number')
                    ->label(__('edition.fields.number'))
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('display_name')
                    ->label(__('edition.fields.display_name'))
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('status')
                    ->label(__('edition.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (EditionStatus $state): string => $state->label()),

                TextColumn::make('starts_on')
                    ->label(__('edition.fields.starts_on'))
                    ->date('d.m.Y')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('races_count')
                    ->label(__('edition.fields.races_count'))
                    ->counts('races'),

                IconColumn::make('is_current')
                    ->label(__('edition.fields.is_current'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('competition')
                    ->label(__('edition.fields.competition'))
                    ->relationship('competition', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(__('edition.fields.status'))
                    ->options(EditionStatus::labels()),
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
