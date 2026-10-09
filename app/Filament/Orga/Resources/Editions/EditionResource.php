<?php

namespace App\Filament\Orga\Resources\Editions;

use App\Domain\Competition\Models\Edition;
use App\Filament\Admin\Resources\Editions\Schemas\EditionForm;
use App\Filament\Orga\Resources\Editions\Pages\EditEdition;
use App\Filament\Orga\Resources\Editions\Pages\ListEditions;
use App\Filament\Orga\Resources\Editions\RelationManagers\EditionDaysRelationManager;
use App\Filament\Orga\Resources\Editions\Tables\EditionsTable;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The orga team's own editions (specification 6.3). Creating and deleting
 * editions stays with the organiser, so this resource only lists and edits.
 */
class EditionResource extends Resource
{
    protected static ?string $model = Edition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'slug';

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

    /**
     * Only the editions this person is responsible for. Without this the list
     * would show every edition and only refuse on click - the rows have to
     * stay out of the query (CLAUDE.md: never only hide in the UI).
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
            EditionDaysRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEditions::route('/'),
            'edit' => EditEdition::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
