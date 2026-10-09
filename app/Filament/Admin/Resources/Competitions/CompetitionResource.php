<?php

namespace App\Filament\Admin\Resources\Competitions;

use App\Domain\Competition\Models\Competition;
use App\Filament\Admin\Resources\Competitions\Pages\CreateCompetition;
use App\Filament\Admin\Resources\Competitions\Pages\EditCompetition;
use App\Filament\Admin\Resources\Competitions\Pages\ListCompetitions;
use App\Filament\Admin\Resources\Competitions\RelationManagers\EditionsRelationManager;
use App\Filament\Admin\Resources\Competitions\Schemas\CompetitionForm;
use App\Filament\Admin\Resources\Competitions\Tables\CompetitionsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompetitionResource extends Resource
{
    protected static ?string $model = Competition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('competition.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('competition.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('competition.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return CompetitionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompetitionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EditionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompetitions::route('/'),
            'create' => CreateCompetition::route('/create'),
            'edit' => EditCompetition::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
