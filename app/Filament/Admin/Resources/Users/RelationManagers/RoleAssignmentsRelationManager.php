<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Domain\Access\Enums\RoleName;
use App\Domain\Access\Models\RoleAssignment;
use App\Domain\Competition\Models\Edition;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

/**
 * Grants and revokes roles with their context (specification chapter 3 and 8).
 * A grant is never edited in place - it is revoked and granted anew, so the
 * audit log reads as what actually happened (B-03).
 */
class RoleAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'roleAssignments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('user.role.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('role_id')
                ->label(__('user.role.fields.role'))
                ->options(fn (): array => Role::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->map(fn (string $name): string => RoleName::tryFrom($name)?->label() ?? $name)
                    ->all())
                ->required()
                ->searchable(),

            Select::make('scope_type')
                ->label(__('user.role.fields.scope_type'))
                ->helperText(__('user.role.hints.scope'))
                ->options([
                    '' => __('user.role.scopes.global'),
                    Edition::class => __('user.role.scopes.edition'),
                ])
                ->default('')
                ->selectablePlaceholder(false)
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('scope_id', 0))
                ->required(),

            Select::make('scope_id')
                ->label(__('user.role.fields.scope'))
                ->options(fn (Get $get): array => $get('scope_type') === Edition::class
                    ? Edition::query()
                        ->with('competition')
                        ->orderByDesc('year')
                        ->get()
                        ->mapWithKeys(fn (Edition $edition): array => [$edition->id => $edition->titleWithNumber()])
                        ->all()
                    : [])
                ->visible(fn (Get $get): bool => $get('scope_type') === Edition::class)
                ->required(fn (Get $get): bool => $get('scope_type') === Edition::class)
                ->default(0)
                ->searchable(),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(__('user.role.label'))
            ->pluralModelLabel(__('user.role.plural_label'))
            ->columns([
                TextColumn::make('role.name')
                    ->label(__('user.role.fields.role'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => RoleName::tryFrom($state)?->label() ?? $state),

                TextColumn::make('scope_id')
                    ->label(__('user.role.fields.scope'))
                    ->state(fn (RoleAssignment $record): string => self::scopeLabel($record)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('user.role.label'))
                    // scope_id stays 0 for a global grant: the unique index
                    // treats NULL values as different and would allow the same
                    // global role twice.
                    ->mutateDataUsing(function (array $data): array {
                        $data['scope_type'] ??= '';
                        $data['scope_id'] = $data['scope_type'] === '' ? 0 : (int) $data['scope_id'];

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }

    private static function scopeLabel(RoleAssignment $assignment): string
    {
        if ($assignment->isGlobal()) {
            return __('user.role.scopes.global');
        }

        $scope = $assignment->scope;

        return $scope instanceof Edition ? $scope->titleWithNumber() : class_basename($scope ?? '');
    }
}
