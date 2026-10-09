<?php

namespace Database\Seeders;

use App\Domain\Competition\Models\Competition;
use App\Support\Concerns\SeedsWithoutAudit;
use Illuminate\Database\Seeder;

/**
 * The organiser's three competitions (specification 1.5). BerlinWoMen is the
 * successor of the BerlinMan: here it is one competition whose editions up to
 * 2024 carry the display name "BerlinMan".
 */
class CompetitionSeeder extends Seeder
{
    use SeedsWithoutAudit;

    /**
     * @var array<int, array{name: string, slug: string, sort: int}>
     */
    public const COMPETITIONS = [
        ['name' => 'Volkstriathlon', 'slug' => 'volkstriathlon', 'sort' => 1],
        ['name' => 'BerlinWoMen', 'slug' => 'berlinwomen', 'sort' => 2],
        ['name' => 'Schüler-Duathlon', 'slug' => 'schueler-duathlon', 'sort' => 3],
    ];

    public function run(): void
    {
        $this->withoutAudit(function (): void {
            foreach (self::COMPETITIONS as $competition) {
                Competition::updateOrCreate(
                    ['slug' => $competition['slug']],
                    [
                        'name' => $competition['name'],
                        'sort' => $competition['sort'],
                        'is_active' => true,
                    ],
                );
            }
        });
    }
}
