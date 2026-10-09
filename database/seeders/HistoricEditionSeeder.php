<?php

namespace Database\Seeders;

use App\Domain\Competition\Enums\Discipline;
use App\Domain\Competition\Enums\EditionStatus;
use App\Domain\Competition\Enums\RaceType;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\EditionDay;
use App\Domain\Competition\Models\Race;
use App\Integrations\Results\Models\WkMapping;
use App\Support\Concerns\SeedsWithoutAudit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use SplFileObject;

/**
 * Takes the checked wk mapping from docs/data/wk-zuordnung.csv into the portal
 * (specification W-33, W-37): 98 wk values of the legacy result database,
 * 27.520 entries, 55 editions and 93 races since 1989.
 *
 * Idempotent throughout, so it may be re-run on an existing database.
 */
class HistoricEditionSeeder extends Seeder
{
    use SeedsWithoutAudit;

    private const SOURCE = 'docs/data/wk-zuordnung.csv';

    /**
     * Display name of the editions a competition ran under until a given year.
     *
     * @var array<string, array{name: string, until: int}>
     */
    private const FORMER_NAMES = [
        'BerlinWoMen' => ['name' => 'BerlinMan', 'until' => 2024],
    ];

    /**
     * Discipline sequence per race name. The quadrathlon of the old BerlinMan
     * had a canoe leg; the school duathlon runs run-bike-run. Everything else
     * is a triathlon.
     *
     * @var array<string, array<int, Discipline>>
     */
    private const SEQUENCES = [
        'Quadrathlon' => [Discipline::Swim, Discipline::Bike, Discipline::Canoe, Discipline::Run],
        'Schüler-Duathlon' => [Discipline::Run, Discipline::Bike, Discipline::Run],
    ];

    private const DEFAULT_SEQUENCE = [Discipline::Swim, Discipline::Bike, Discipline::Run];

    /**
     * @var array<int, string>
     */
    private const RELAYS = ['Mitteldistanz-Staffel'];

    /**
     * The 32nd Volkstriathlon (2020/21) was called off because of Corona. It
     * has no results and therefore no row in the CSV, but it is created so the
     * edition numbers stay a complete sequence from 1 to 38.
     *
     * @var array<string, mixed>
     */
    private const CANCELLED = [
        'competition' => 'Volkstriathlon',
        'year' => 2020,
        'number' => 32,
        'note' => 'Wegen Corona ausgefallen; der Wettkampf wurde 2020 und 2021 nicht ausgetragen.',
    ];

    public function run(): void
    {
        $rows = $this->readRows();

        $this->withoutAudit(function () use ($rows): void {
            foreach ($this->groupByEdition($rows) as $group) {
                $this->seedEdition($group);
            }

            $this->seedCancelledEdition();
        });
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readRows(): array
    {
        $path = base_path(self::SOURCE);

        if (! is_readable($path)) {
            throw new RuntimeException("Die Seed-Datei {$path} ist nicht lesbar.");
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $header = null;
        $rows = [];

        foreach ($file as $line) {
            if (! is_array($line) || $line === [null]) {
                continue;
            }

            if ($header === null) {
                $header = $line;

                continue;
            }

            $rows[] = array_map(
                fn (?string $value): string => trim((string) $value),
                array_combine($header, $line),
            );
        }

        return $rows;
    }

    /**
     * Rows grouped by competition and year - one edition each.
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, array<int, array<string, string>>>
     */
    private function groupByEdition(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $groups[$row['wettkampf'].'|'.$row['jahr']][] = $row;
        }

        return $groups;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function seedEdition(array $rows): void
    {
        $first = $rows[0];
        $competition = $this->competition($first['wettkampf']);
        $year = (int) $first['jahr'];

        $edition = Edition::updateOrCreate(
            ['competition_id' => $competition->id, 'slug' => (string) $year],
            [
                'year' => $year,
                'number' => $first['ausgabe_nr'] === '' ? null : (int) $first['ausgabe_nr'],
                'display_name' => $this->formerName($first['wettkampf'], $year),
                'status' => EditionStatus::Archived,
            ],
        );

        $days = $this->seedDays($edition, $rows);

        foreach ($this->groupByRace($rows) as $raceName => $raceRows) {
            $race = $this->seedRace($edition, $raceName, $raceRows, $days);

            foreach ($raceRows as $row) {
                WkMapping::updateOrCreate(
                    ['wk' => $row['wk']],
                    [
                        'race_id' => $race->id,
                        'edition_day_id' => $days[$row['tag_datum']] ?? null,
                        'status' => $row['status'],
                        'note' => $row['hinweis'] === '' ? null : $row['hinweis'],
                        'expected_entries' => $row['eintraege'] === '' ? null : (int) $row['eintraege'],
                    ],
                );
            }
        }
    }

    /**
     * Race days of an edition, keyed by the raw tag_datum value of the CSV.
     *
     * The column carries either a date (24.05.2013) or just a weekday label
     * (Samstag) - for the 25th Volkstriathlon only "two days" is known, not
     * which dates. Both cases become a day, the label one without a date.
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, int>
     */
    private function seedDays(Edition $edition, array $rows): array
    {
        $values = array_values(array_unique(array_filter(array_column($rows, 'tag_datum'))));
        $days = [];

        foreach ($values as $sort => $value) {
            $date = $this->parseDate($value);

            $day = EditionDay::updateOrCreate(
                ['edition_id' => $edition->id, 'sort' => $sort],
                [
                    'date' => $date,
                    'label' => $date === null ? $value : null,
                ],
            );

            $days[$value] = $day->id;
        }

        if ($values !== []) {
            $dates = array_filter(array_map(fn (string $value): ?Carbon => $this->parseDate($value), $values));

            $edition->update([
                'starts_on' => $dates === [] ? null : min($dates),
                'ends_on' => $dates === [] ? null : max($dates),
            ]);
        }

        return $days;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, array<int, array<string, string>>>
     */
    private function groupByRace(array $rows): array
    {
        $races = [];

        foreach ($rows as $row) {
            $races[$row['wettbewerb']][] = $row;
        }

        return $races;
    }

    /**
     * One race per distinct name, which merges the wk values that belong
     * together: the MiniMan 2021 was recorded per school class, the
     * Jedermensch-Sprint 2008 under two spellings.
     *
     * @param  array<int, array<string, string>>  $rows
     * @param  array<string, int>  $days
     */
    private function seedRace(Edition $edition, string $name, array $rows, array $days): Race
    {
        // A race points at a single day. When its wk values span several days -
        // as the 25th Volkstriathlon does - the day stays open on the race and
        // is carried by the wk mapping instead.
        $rowDays = array_values(array_unique(array_filter(array_column($rows, 'tag_datum'))));
        $dayId = count($rowDays) === 1 ? ($days[$rowDays[0]] ?? null) : null;

        return Race::updateOrCreate(
            ['edition_id' => $edition->id, 'slug' => Str::slug($name)],
            [
                'name' => $name,
                'edition_day_id' => $dayId,
                'type' => in_array($name, self::RELAYS, true) ? RaceType::Relay : RaceType::Individual,
                'discipline_sequence' => self::SEQUENCES[$name] ?? self::DEFAULT_SEQUENCE,
                'is_public' => true,
            ],
        );
    }

    private function seedCancelledEdition(): void
    {
        $competition = $this->competition(self::CANCELLED['competition']);

        Edition::updateOrCreate(
            ['competition_id' => $competition->id, 'slug' => (string) self::CANCELLED['year']],
            [
                'year' => self::CANCELLED['year'],
                'number' => self::CANCELLED['number'],
                'status' => EditionStatus::Cancelled,
                'note' => self::CANCELLED['note'],
            ],
        );
    }

    private function competition(string $name): Competition
    {
        $competition = Competition::query()->where('name', $name)->first();

        if ($competition === null) {
            throw new RuntimeException("Der Wettkampf {$name} fehlt; der CompetitionSeeder muss vorher laufen.");
        }

        return $competition;
    }

    private function formerName(string $competition, int $year): ?string
    {
        $former = self::FORMER_NAMES[$competition] ?? null;

        return $former !== null && $year <= $former['until'] ? $former['name'] : null;
    }

    /**
     * Parses a German date; returns null when the value is a label like
     * "Samstag" instead of a date.
     */
    private function parseDate(string $value): ?Carbon
    {
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $value) !== 1) {
            return null;
        }

        return Carbon::createFromFormat('d.m.Y', $value)->startOfDay();
    }
}
