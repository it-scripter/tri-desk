<?php

namespace App\Filament\Admin\Resources\Activities;

use App\Filament\Admin\Resources\Activities\Pages\ListActivities;
use App\Filament\Admin\Resources\Activities\Tables\ActivitiesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * The audit log (specification B-03, 6.2), read only. Entries are written by
 * the models themselves and must never be editable from the UI - that would
 * defeat the purpose of the log.
 */
class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 90;

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function getModelLabel(): string
    {
        return __('audit.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('audit.resource.plural_label');
    }

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
    }

    /**
     * Only entries of the audit log, not whatever a package may write into the
     * same table. Filtered by column rather than by the inLog scope, which is
     * not visible on the generic builder Filament hands over.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('log_name', 'audit');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * German label for a logged model, e.g. "Ausgabe" for an Edition.
     */
    public static function subjectTypeLabel(?string $class): string
    {
        if ($class === null) {
            return '-';
        }

        $key = 'audit.subjects.'.Str::snake(class_basename($class));

        return __($key) === $key ? class_basename($class) : __($key);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }
}
