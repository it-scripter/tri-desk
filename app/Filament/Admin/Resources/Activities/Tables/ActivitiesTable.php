<?php

namespace App\Filament\Admin\Resources\Activities\Tables;

use App\Filament\Admin\Resources\Activities\ActivityResource;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * Read-only view of the audit log, filterable by model, event, person and
 * period - the questions actually asked when tracing a change.
 */
class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('audit.resource.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('subject_type')
                    ->label(__('audit.resource.subject_type'))
                    ->formatStateUsing(fn (?string $state): string => ActivityResource::subjectTypeLabel($state)),

                TextColumn::make('subject_id')
                    ->label(__('audit.resource.subject'))
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : "#{$state}"),

                TextColumn::make('description')
                    ->label(__('audit.resource.event'))
                    ->badge(),

                TextColumn::make('causer_id')
                    ->label(__('audit.resource.causer'))
                    ->formatStateUsing(fn (?int $state, Activity $record): string => self::causerName($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHasMorph(
                        'causer',
                        [User::class],
                        fn (Builder $causer) => $causer
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"),
                    )),

                TextColumn::make('attribute_changes')
                    ->label(__('audit.resource.changes'))
                    ->formatStateUsing(fn (Activity $record): string => self::changeSummary($record))
                    ->wrap()
                    ->limit(80),
            ])
            ->filters([
                SelectFilter::make('subject_type')
                    ->label(__('audit.resource.subject_type'))
                    ->options(fn (): array => Activity::query()
                        ->inLog('audit')
                        ->whereNotNull('subject_type')
                        ->distinct()
                        ->pluck('subject_type', 'subject_type')
                        ->map(fn (string $class): string => ActivityResource::subjectTypeLabel($class))
                        ->all()),

                SelectFilter::make('event')
                    ->label(__('audit.resource.event'))
                    ->options(fn (): array => __('audit.events')),

                SelectFilter::make('causer_id')
                    ->label(__('audit.resource.causer'))
                    ->options(fn (): array => User::query()
                        ->orderBy('last_name')
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [$user->id => $user->fullName()])
                        ->all()),

                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')->label(__('audit.resource.period_from')),
                        DatePicker::make('until')->label(__('audit.resource.period_until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $from) => $q->whereDate('created_at', '>=', $from))
                        ->when($data['until'] ?? null, fn (Builder $q, string $until) => $q->whereDate('created_at', '<=', $until))),
            ])
            ->recordActions([
                ViewAction::make()->schema(fn (Schema $schema): Schema => $schema->components([
                    TextEntry::make('created_at')
                        ->label(__('audit.resource.created_at'))
                        ->dateTime('d.m.Y H:i:s'),
                    TextEntry::make('subject_type')
                        ->label(__('audit.resource.subject_type'))
                        ->formatStateUsing(fn (?string $state): string => ActivityResource::subjectTypeLabel($state)),
                    TextEntry::make('description')
                        ->label(__('audit.resource.event')),
                    TextEntry::make('causer_id')
                        ->label(__('audit.resource.causer'))
                        ->formatStateUsing(fn (?int $state, Activity $record): string => self::causerName($record)),
                    KeyValueEntry::make('changes')
                        ->label(__('audit.resource.changes'))
                        ->state(fn (Activity $record): array => self::changeRows($record)),
                ])),
            ])
            ->toolbarActions([]);
    }

    private static function causerName(Activity $activity): string
    {
        $causer = $activity->causer;

        return $causer instanceof User ? $causer->fullName() : __('audit.resource.system');
    }

    /**
     * @return array<string, string>
     */
    private static function changeRows(Activity $activity): array
    {
        $changes = $activity->attribute_changes;
        $new = (array) ($changes['attributes'] ?? []);
        $old = (array) ($changes['old'] ?? []);

        $rows = [];

        foreach ($new as $field => $value) {
            $rows[$field] = array_key_exists($field, $old)
                ? sprintf('%s %s %s %s', __('audit.resource.from'), self::stringify($old[$field]), __('audit.resource.to'), self::stringify($value))
                : self::stringify($value);
        }

        return $rows;
    }

    private static function changeSummary(Activity $activity): string
    {
        $rows = self::changeRows($activity);

        if ($rows === []) {
            return '-';
        }

        return collect($rows)
            ->map(fn (string $value, string $field): string => "{$field}: {$value}")
            ->implode(', ');
    }

    private static function stringify(mixed $value): string
    {
        return match (true) {
            $value === null => '-',
            is_bool($value) => $value ? 'ja' : 'nein',
            is_array($value) => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }
}
