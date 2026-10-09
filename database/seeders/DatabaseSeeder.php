<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Model events stay muted while seeding: the audit log would otherwise
     * fill up with several hundred entries that nobody caused, and the seeders
     * set the few derived fields themselves.
     */
    use WithoutModelEvents;

    /**
     * Seed the application's database. Everything listed here is idempotent,
     * so the seeders may be re-run on an existing database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CompetitionSeeder::class,
            HistoricEditionSeeder::class,
            Volkstriathlon2027Seeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DevUserSeeder::class);
        }
    }
}
