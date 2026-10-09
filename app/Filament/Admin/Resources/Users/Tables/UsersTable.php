<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Domain\Access\Enums\RoleName;
use App\Domain\Access\Models\RoleAssignment;
use App\Domain\Competition\Models\Edition;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_name')
            ->columns([
                TextColumn::make('last_name')
                    ->label(__('user.fields.name'))
                    ->state(fn (User $record): string => $record->fullName())
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('user.fields.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label(__('user.fields.phone'))
                    ->placeholder('-'),

                TextColumn::make('roles')
                    ->label(__('user.fields.roles'))
                    ->badge()
                    ->state(fn (User $record): array => $record->roleAssignments
                        ->map(fn (RoleAssignment $assignment): string => self::assignmentLabel($assignment))
                        ->unique()
                        ->values()
                        ->all()),

                TextColumn::make('created_at')
                    ->label(__('user.fields.created_at'))
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('user.fields.roles'))
                    ->options(RoleName::labels())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $role) => $q->whereHas(
                            'roleAssignments.role',
                            fn (Builder $r) => $r->where('name', $role),
                        ),
                    )),
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

    /**
     * Role with its scope, e.g. "Orga-Team (Volkstriathlon 2027)".
     */
    private static function assignmentLabel(RoleAssignment $assignment): string
    {
        $role = $assignment->role?->name;
        $label = $role === null ? '?' : (RoleName::tryFrom($role)?->label() ?? $role);

        if ($assignment->isGlobal()) {
            return $label;
        }

        $scope = $assignment->scope;

        $title = $scope instanceof Edition
            ? $scope->titleWithNumber()
            : class_basename($scope ?? $assignment->scope_type);

        return "{$label} ({$title})";
    }
}
