<?php

namespace App\Filament\Admin\Resources\Competitions\Tables;

use App\Domain\Competition\Models\Competition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CompetitionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')
                    ->label(__('competition.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('competition.fields.slug'))
                    ->searchable()
                    ->badge(),

                TextColumn::make('editions_count')
                    ->label(__('competition.fields.editions_count'))
                    ->counts('editions'),

                TextColumn::make('current_edition')
                    ->label(__('competition.fields.current_edition'))
                    ->state(fn (Competition $record): string => $record->currentEdition()?->titleWithNumber() ?? '-'),

                IconColumn::make('is_active')
                    ->label(__('competition.fields.is_active'))
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('competition.fields.is_active')),
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
