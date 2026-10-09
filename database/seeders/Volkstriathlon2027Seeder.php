<?php

namespace Database\Seeders;

use App\Domain\Competition\Enums\Discipline;
use App\Domain\Competition\Enums\EditionStatus;
use App\Domain\Competition\Enums\RaceType;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\EditionDay;
use App\Domain\Competition\Models\Race;
use App\Support\Concerns\SeedsWithoutAudit;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * The upcoming edition: the 38th Volkstriathlon on 27.06.2027, registration
 * opening 15.02.2027 (specification 10.5). The 2026 edition is the 37th and
 * number 32 was called off, so 2027 is the 38th.
 */
class Volkstriathlon2027Seeder extends Seeder
{
    use SeedsWithoutAudit;

    private const YEAR = 2027;

    private const NUMBER = 38;

    private const RACE_DAY = '2027-06-27';

    private const REGISTRATION_OPENS = '2027-02-15 10:00:00';

    /**
     * @var array<int, array{name: string, slug: string, type: RaceType, sequence: array<int, Discipline>, sort: int}>
     */
    private const RACES = [
        [
            'name' => 'Volkstriathlon',
            'slug' => 'volkstriathlon',
            'type' => RaceType::Individual,
            'sequence' => [Discipline::Swim, Discipline::Bike, Discipline::Run],
            'sort' => 0,
        ],
        [
            'name' => 'Volxtri4kids',
            'slug' => 'volxtri4kids',
            'type' => RaceType::Individual,
            'sequence' => [Discipline::Swim, Discipline::Bike, Discipline::Run],
            'sort' => 1,
        ],
    ];

    public function run(): void
    {
        $this->withoutAudit(function (): void {
            $this->seedEdition();
        });
    }

    private function seedEdition(): void
    {
        $competition = Competition::query()->where('slug', 'volkstriathlon')->first();

        if ($competition === null) {
            throw new RuntimeException('Der Wettkampf Volkstriathlon fehlt; der CompetitionSeeder muss vorher laufen.');
        }

        $edition = Edition::updateOrCreate(
            ['competition_id' => $competition->id, 'slug' => (string) self::YEAR],
            [
                'year' => self::YEAR,
                'number' => self::NUMBER,
                'status' => EditionStatus::Planned,
                'starts_on' => self::RACE_DAY,
                'ends_on' => self::RACE_DAY,
                'registration_opens_at' => self::REGISTRATION_OPENS,
                'is_current' => true,
            ],
        );

        // Every other edition of this competition is historic, so the current
        // flag is set explicitly here: the seeders run with model events
        // muted, which is where the exclusivity would otherwise come from.
        Edition::query()
            ->where('competition_id', $competition->id)
            ->whereKeyNot($edition->getKey())
            ->where('is_current', true)
            ->update(['is_current' => false]);

        $day = EditionDay::updateOrCreate(
            ['edition_id' => $edition->id, 'sort' => 0],
            ['date' => self::RACE_DAY, 'label' => 'Sonntag'],
        );

        foreach (self::RACES as $race) {
            Race::updateOrCreate(
                ['edition_id' => $edition->id, 'slug' => $race['slug']],
                [
                    'name' => $race['name'],
                    'edition_day_id' => $day->id,
                    'type' => $race['type'],
                    'discipline_sequence' => $race['sequence'],
                    'sort' => $race['sort'],
                    'is_public' => true,
                ],
            );
        }
    }
}
