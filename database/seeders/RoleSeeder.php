<?php

namespace Database\Seeders;

use App\Domain\Access\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and the coarse permission catalogue of the specification, chapter 3.
 * Permissions are granted per role here; the context (global, edition, org
 * unit) comes from the role_assignments table and is checked in the policies.
 */
class RoleSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'competition.viewAny',
        'competition.view',
        'competition.create',
        'competition.update',
        'competition.delete',
        'edition.viewAny',
        'edition.view',
        'edition.create',
        'edition.update',
        'edition.delete',
        'edition.copy',
        'race.viewAny',
        'race.view',
        'race.create',
        'race.update',
        'race.delete',
        'price.view',
        'price.manage',
        'cancellation.view',
        'cancellation.manage',
        'registration.view',
        'registration.manage',
        'user.viewAny',
        'user.view',
        'user.create',
        'user.update',
        'user.delete',
        'role.assign',
        'activity.view',
    ];

    /**
     * Permissions per role. The super admin is not listed: Gate::before lets
     * that role pass everything, so an explicit list could only drift.
     *
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        RoleName::Orga->value => [
            'competition.viewAny',
            'competition.view',
            'edition.viewAny',
            'edition.view',
            'edition.update',
            'race.viewAny',
            'race.view',
            'race.create',
            'race.update',
            'race.delete',
            'price.view',
            'price.manage',
            'cancellation.view',
            'cancellation.manage',
            'registration.view',
            'registration.manage',
            'user.viewAny',
            'user.view',
        ],
        RoleName::Redaktion->value => [
            'competition.view',
            'edition.viewAny',
            'edition.view',
        ],
        RoleName::Teamleitung->value => [
            'edition.view',
        ],
        RoleName::Helfende->value => [],
        RoleName::Teilnehmende->value => [],
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        // spatie resolves findOrCreate and syncPermissions through its cache,
        // so the cache has to be dropped before reading and again after new
        // permissions were written - otherwise a re-run inserts duplicates.
        $registrar->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        $registrar->forgetCachedPermissions();

        foreach (RoleName::cases() as $roleName) {
            $role = Role::findOrCreate($roleName->value);

            if ($roleName === RoleName::SuperAdmin) {
                continue;
            }

            $role->syncPermissions(self::ROLE_PERMISSIONS[$roleName->value]);
        }

        $registrar->forgetCachedPermissions();
    }
}
