<?php

namespace App\Domain\Access\Concerns;

use App\Domain\Access\Enums\RoleName;
use App\Domain\Access\Models\RoleAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Resolves roles that are granted in a context: globally, for a single edition
 * or for a single org unit. A global assignment always outranks a scoped one,
 * so it satisfies every scoped check as well.
 */
trait HasRoleAssignments
{
    /**
     * @return HasMany<RoleAssignment, $this>
     */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    /**
     * Grant a role. Omitting the scope grants it globally.
     */
    public function assignRoleFor(RoleName|string $role, ?Model $scope = null): RoleAssignment
    {
        /** @var RoleAssignment $assignment */
        $assignment = $this->roleAssignments()->firstOrCreate([
            'role_id' => $this->resolveRole($role)->getKey(),
            'scope_type' => $scope?->getMorphClass() ?? '',
            'scope_id' => $scope?->getKey() ?? 0,
        ]);

        $this->unsetRelation('roleAssignments');

        return $assignment;
    }

    /**
     * Revoke a role in exactly the given scope.
     */
    public function removeRoleFor(RoleName|string $role, ?Model $scope = null): void
    {
        // Deleted one by one, not with a mass delete: a query builder delete
        // skips the model events and the revocation would never reach the
        // audit log (B-03).
        $this->roleAssignments()
            ->where('role_id', $this->resolveRole($role)->getKey())
            ->where('scope_type', $scope?->getMorphClass() ?? '')
            ->where('scope_id', $scope?->getKey() ?? 0)
            ->get()
            ->each(fn (RoleAssignment $assignment) => $assignment->delete());

        $this->unsetRelation('roleAssignments');
    }

    /**
     * Does the user hold this role in the given scope? A global assignment of
     * the same role counts, a scoped one does not count outside its scope.
     */
    public function hasRoleFor(RoleName|string $role, ?Model $scope = null): bool
    {
        $name = $role instanceof RoleName ? $role->value : $role;

        return $this->loadedRoleAssignments()
            ->contains(function (RoleAssignment $assignment) use ($name, $scope): bool {
                if ($assignment->role?->name !== $name) {
                    return false;
                }

                return $assignment->isGlobal() || $this->scopeMatches($assignment, $scope);
            });
    }

    /**
     * Does the user hold any of these roles in the given scope?
     *
     * @param  array<int, RoleName|string>  $roles
     */
    public function hasAnyRoleFor(array $roles, ?Model $scope = null): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRoleFor($role, $scope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does any role the user holds in this scope carry the permission? This is
     * the link between the own assignment table and the spatie permission
     * catalogue: the user is never attached to a permission directly.
     *
     * The permission is compared by name instead of through
     * Role::hasPermissionTo(), which throws when the permission is unknown -
     * a missing permission must read as "not allowed", not as an error.
     */
    public function hasPermissionFor(string $permission, ?Model $scope = null): bool
    {
        return $this->loadedRoleAssignments()
            ->filter(fn (RoleAssignment $assignment): bool => $assignment->isGlobal() || $this->scopeMatches($assignment, $scope))
            ->contains(fn (RoleAssignment $assignment): bool => (bool) $assignment->role?->permissions
                ->contains('name', $permission));
    }

    /**
     * Primary keys of the scopes of this type in which the user holds a role
     * that carries the permission. This is what narrows a Filament table to
     * the editions a person is actually responsible for.
     *
     * Global assignments are not part of the result: they are not bound to a
     * scope, so a caller has to check hasPermissionFor() separately. Returning
     * "all ids" here would be a lie about which rows were matched.
     *
     * @param  class-string<Model>  $scopeType
     * @return Collection<int, int>
     */
    public function scopeIdsWithPermission(string $scopeType, string $permission): Collection
    {
        $morphClass = (new $scopeType)->getMorphClass();

        return $this->loadedRoleAssignments()
            ->filter(fn (RoleAssignment $assignment): bool => $assignment->scope_type === $morphClass
                && (bool) $assignment->role?->permissions->contains('name', $permission))
            ->map(fn (RoleAssignment $assignment): int => $assignment->scope_id)
            ->unique()
            ->values();
    }

    /**
     * Does the user hold the permission anywhere - globally or in at least one
     * scope of this type? Answers "may this person open the list at all",
     * before the list itself is narrowed down.
     *
     * @param  class-string<Model>  $scopeType
     */
    public function hasPermissionInAnyScope(string $permission, string $scopeType): bool
    {
        return $this->hasPermissionFor($permission)
            || $this->scopeIdsWithPermission($scopeType, $permission)->isNotEmpty();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRoleFor(RoleName::SuperAdmin);
    }

    /**
     * Role names the user holds in the given scope, global ones included.
     *
     * @return Collection<int, string>
     */
    public function roleNamesFor(?Model $scope = null): Collection
    {
        /** @var Collection<int, string> $names */
        $names = $this->loadedRoleAssignments()
            ->filter(fn (RoleAssignment $assignment): bool => $assignment->isGlobal() || $this->scopeMatches($assignment, $scope))
            ->map(fn (RoleAssignment $assignment): ?string => $assignment->role?->name)
            ->filter()
            ->unique()
            ->values();

        return $names;
    }

    /**
     * Primary keys of the scopes of the given type the user holds any role in.
     * Used to narrow Filament tables to the user's own editions or org units.
     *
     * @param  class-string<Model>  $scopeType
     * @param  array<int, RoleName|string>  $roles
     * @return Collection<int, int>
     */
    public function scopeIdsFor(string $scopeType, array $roles = []): Collection
    {
        $morphClass = (new $scopeType)->getMorphClass();
        $names = array_map(fn (RoleName|string $role): string => $role instanceof RoleName ? $role->value : $role, $roles);

        return $this->loadedRoleAssignments()
            ->filter(function (RoleAssignment $assignment) use ($morphClass, $names): bool {
                if ($assignment->scope_type !== $morphClass) {
                    return false;
                }

                return $names === [] || in_array($assignment->role?->name, $names, true);
            })
            ->map(fn (RoleAssignment $assignment): int => $assignment->scope_id)
            ->unique()
            ->values();
    }

    /**
     * Does the user hold any of these roles in any context, global included?
     * Answers "may this person use the orga panel" without naming a single
     * edition; which edition they may touch is a question for the policies.
     *
     * @param  array<int, RoleName|string>  $roles
     */
    public function holdsAnyRole(array $roles): bool
    {
        $names = array_map(fn (RoleName|string $role): string => $role instanceof RoleName ? $role->value : $role, $roles);

        return $this->loadedRoleAssignments()
            ->contains(fn (RoleAssignment $assignment): bool => in_array($assignment->role?->name, $names, true));
    }

    /**
     * @return Collection<int, RoleAssignment>
     */
    protected function loadedRoleAssignments(): Collection
    {
        if (! $this->relationLoaded('roleAssignments')) {
            $this->load('roleAssignments.role.permissions');
        }

        /** @var Collection<int, RoleAssignment> $assignments */
        $assignments = $this->getRelation('roleAssignments');

        return $assignments;
    }

    protected function scopeMatches(RoleAssignment $assignment, ?Model $scope): bool
    {
        return $scope !== null
            && $assignment->scope_type === $scope->getMorphClass()
            && $assignment->scope_id === $scope->getKey();
    }

    protected function resolveRole(RoleName|string $role): Role
    {
        $name = $role instanceof RoleName ? $role->value : $role;

        /** @var Role $model */
        $model = Role::findByName($name);

        return $model;
    }
}
