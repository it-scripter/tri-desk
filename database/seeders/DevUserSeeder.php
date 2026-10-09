<?php

namespace Database\Seeders;

use App\Domain\Access\Enums\RoleName;
use App\Domain\Competition\Models\Edition;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Accounts for local development only, with known passwords. One per panel, so
 * the separation between /admin, /orga and /team can be checked by hand.
 *
 * The orga account is bound to the upcoming edition, which is what makes the
 * scoped lists in the orga panel visible. The team account stays global until
 * org units exist (phase 4).
 */
class DevUserSeeder extends Seeder
{
    /**
     * @var array<int, array{email: string, first_name: string, last_name: string, role: RoleName, scope?: string}>
     */
    private const ACCOUNTS = [
        ['email' => 'admin@trideskapp.test', 'first_name' => 'Vera', 'last_name' => 'Veranstalter', 'role' => RoleName::SuperAdmin],
        ['email' => 'orga@trideskapp.test', 'first_name' => 'Olga', 'last_name' => 'Orga', 'role' => RoleName::Orga, 'scope' => 'current'],
        ['email' => 'team@trideskapp.test', 'first_name' => 'Tim', 'last_name' => 'Teamleitung', 'role' => RoleName::Teamleitung],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DevUserSeeder darf nur in lokalen Umgebungen laufen.');
        }

        foreach (self::ACCOUNTS as $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'first_name' => $account['first_name'],
                    'last_name' => $account['last_name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $scope = ($account['scope'] ?? null) === 'current' ? $this->currentEdition() : null;

            $user->assignRoleFor($account['role'], $scope);

            $context = $scope === null ? 'global' : $scope->titleWithNumber();
            $this->command->info("{$account['email']} – Rolle {$account['role']->value} ({$context})");
        }

        $this->command->warn('Passwort aller Entwickler-Konten: password');
    }

    /**
     * The edition the orga account is responsible for.
     */
    private function currentEdition(): ?Edition
    {
        return Edition::query()->where('is_current', true)->orderByDesc('year')->first();
    }
}
