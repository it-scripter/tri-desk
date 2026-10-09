<?php

namespace App\Filament\Admin\Resources\Editions;

use App\Domain\Competition\Models\Edition;
use App\Filament\Admin\Resources\Editions\Pages\CreateEdition;
use App\Filament\Admin\Resources\Editions\Pages\EditEdition;
use App\Filament\Admin\Resources\Editions\Pages\ListEditions;
use App\Filament\Admin\Resources\Editions\RelationManagers\EditionDaysRelationManager;
use App\Filament\Admin\Resources\Editions\RelationManagers\RacesRelationManager;
use App\Filament\Admin\Resources\Editions\Schemas\EditionForm;
use App\Filament\Admin\Resources\Editions\Tables\EditionsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EditionResource extends Resource
{
    protected static ?string $model = Edition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'slug';

    public static function getNavigationGroup(): ?string
    {
        return __('competition.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('edition.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('edition.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return EditionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EditionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EditionDaysRelationManager::class,
            RacesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEditions::route('/'),
            'create' => CreateEdition::route('/create'),
            'edit' => EditEdition::route('/{record}/edit'),
        ];
    }
}
