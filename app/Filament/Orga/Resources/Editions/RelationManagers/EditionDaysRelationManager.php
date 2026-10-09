<?php

namespace App\Filament\Orga\Resources\Editions\RelationManagers;

use App\Domain\Competition\Models\Edition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EditionDaysRelationManager extends RelationManager
{
    protected static string $relationship = 'days';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('edition.day.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')
                ->label(__('edition.day.fields.date'))
                ->helperText(__('edition.day.hints.date'))
                ->displayFormat('d.m.Y'),

            TextInput::make('label')
                ->label(__('edition.day.fields.label'))
                ->maxLength(255),

            TextInput::make('sort')
                ->label(__('edition.day.fields.sort'))
                ->numeric()
                ->default(function (RelationManager $livewire): int {
                    $edition = $livewire->getOwnerRecord();

                    return $edition instanceof Edition ? $edition->days()->count() : 0;
                })
                ->required(),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->modelLabel(__('edition.day.label'))
            ->pluralModelLabel(__('edition.day.plural_label'))
            ->columns([
                TextColumn::make('sort')
                    ->label(__('edition.day.fields.sort'))
                    ->sortable(),

                TextColumn::make('date')
                    ->label(__('edition.day.fields.date'))
                    ->date('d.m.Y')
                    ->placeholder('-'),

                TextColumn::make('label')
                    ->label(__('edition.day.fields.label'))
                    ->placeholder('-'),

                TextColumn::make('races_count')
                    ->label(__('edition.fields.races_count'))
                    ->counts('races'),
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
