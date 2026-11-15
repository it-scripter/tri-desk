<?php

namespace App\Filament\Orga\Resources\Registrations;

use App\Domain\Competition\Models\Registration;
use App\Filament\Orga\Resources\Registrations\Pages\ListRegistrations;
use App\Filament\Orga\Resources\Registrations\Pages\ViewRegistration;
use App\Filament\Orga\Resources\Registrations\Schemas\RegistrationInfolist;
use App\Filament\Orga\Resources\Registrations\Tables\RegistrationsTable;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Registrations of the orga team's own editions (specification 6.3). Read and
 * cancel only for now; the orga team enters registrations through the form
 * of package 2b-2.
 */
class RegistrationResource extends Resource
{
    protected static ?string $model = Registration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 30;

    public static function getModelLabel(): string
    {
        return __('registration.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('registration.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return RegistrationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RegistrationsTable::configure($table);
    }

    /**
     * Registrations of the own editions only - the rows of other editions
     * never enter the query.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        $query = parent::getEloquentQuery()->with(['race.edition', 'participants']);

        if (! $user instanceof User) {
            return $query->whereKey([]);
        }

        return $query->scopes(['visibleTo' => [$user]]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistrations::route('/'),
            'view' => ViewRegistration::route('/{record}'),
        ];
    }
}
