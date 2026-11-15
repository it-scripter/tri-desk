<?php

namespace App\Filament\Orga\Resources\Races;

use App\Domain\Competition\Models\Race;
use App\Filament\Orga\Resources\Races\Pages\CreateRace;
use App\Filament\Orga\Resources\Races\Pages\EditRace;
use App\Filament\Orga\Resources\Races\Pages\ListRaces;
use App\Filament\Orga\Resources\Races\Tables\RacesTable;
use App\Filament\Shared\RaceSchema;
use App\Filament\Shared\RelationManagers\PriceTiersRelationManager;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Races of the orga team's own editions (specification 6.3, W-02).
 */
class RaceResource extends Resource
{
    protected static ?string $model = Race::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('race.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('race.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(RaceSchema::fields());
    }

    public static function table(Table $table): Table
    {
        return RacesTable::configure($table);
    }

    /**
     * Races of the own editions only - the rows of other editions never enter
     * the query.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        $query = parent::getEloquentQuery();

        // No signed in user means no rows, never all rows.
        if (! $user instanceof User) {
            return $query->whereKey([]);
        }

        // Called through scopes() rather than ->visibleTo(): the builder
        // Filament hands over is typed for a generic model, so the scope is
        // not visible on it. The rule itself stays defined once, on the model.
        return $query->scopes(['visibleTo' => [$user]]);
    }

    public static function getRelations(): array
    {
        return [
            PriceTiersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRaces::route('/'),
            'create' => CreateRace::route('/create'),
            'edit' => EditRace::route('/{record}/edit'),
        ];
    }
}
