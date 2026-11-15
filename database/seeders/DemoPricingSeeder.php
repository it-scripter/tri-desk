<?php

namespace Database\Seeders;

use App\Domain\Competition\Enums\DiverseScoring;
use App\Domain\Competition\Models\AgeGroupScheme;
use App\Domain\Competition\Models\CancellationRule;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceOption;
use App\Support\Concerns\SeedsWithoutAudit;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Example prices, options, cancellation rules and an age group scheme for the
 * 2027 Volkstriathlon (W-03 to W-05), for local development only.
 *
 * None of the figures are the organiser's: prices and the age group codes are
 * still open (specification chapter 11), so this seeder must never run in
 * production - it is called from the DatabaseSeeder in the local environment
 * only, next to the DevUserSeeder.
 */
class DemoPricingSeeder extends Seeder
{
    use SeedsWithoutAudit;

    public function run(): void
    {
        $this->withoutAudit(function (): void {
            $edition = Edition::query()
                ->whereHas('competition', fn ($query) => $query->where('slug', 'volkstriathlon'))
                ->where('year', 2027)
                ->first() ?? throw new RuntimeException('Die Ausgabe 2027 fehlt; der Volkstriathlon2027Seeder muss vorher laufen.');

            $scheme = $this->seedScheme();

            foreach ($edition->races as $race) {
                $race->update(['age_group_scheme_id' => $scheme->id]);
                $this->seedTiers($race);
            }

            $this->seedOptions($edition);
            $this->seedCancellationRules($edition);
        });
    }

    /**
     * Five-year groups from 20, separate for men and women, juniors below.
     * The codes are placeholders until the list of the legacy result
     * database is known.
     */
    private function seedScheme(): AgeGroupScheme
    {
        $scheme = AgeGroupScheme::updateOrCreate(
            ['slug' => 'beispiel-fuenfjahresklassen'],
            [
                'name' => 'Beispiel: Fünfjahresklassen',
                'diverse_scoring' => DiverseScoring::Separate,
                'description' => 'Beispieldaten für die Entwicklung, nicht das Schema des Veranstalters.',
            ],
        );

        $sort = 0;

        foreach (['m' => 'M', 'w' => 'W'] as $gender => $prefix) {
            $scheme->ageGroups()->updateOrCreate(
                ['code' => "{$prefix}J"],
                ['name' => 'Jugend', 'gender' => $gender, 'min_age' => null, 'max_age' => 19, 'sort' => $sort++],
            );

            for ($from = 20; $from <= 70; $from += 5) {
                $scheme->ageGroups()->updateOrCreate(
                    ['code' => "{$prefix}{$from}"],
                    ['gender' => $gender, 'min_age' => $from, 'max_age' => $from === 70 ? null : $from + 4, 'sort' => $sort++],
                );
            }
        }

        $scheme->ageGroups()->updateOrCreate(
            ['code' => 'D'],
            ['name' => 'Divers', 'gender' => 'd', 'min_age' => null, 'max_age' => null, 'sort' => $sort],
        );

        return $scheme;
    }

    private function seedTiers(Race $race): void
    {
        $base = $race->slug === 'volxtri4kids' ? 1000 : 4000;

        $tiers = [
            ['name' => 'Early Bird', 'amount_cents' => $base - 500, 'valid_from' => '2027-02-15 10:00:00', 'valid_until' => '2027-03-31 23:59:59', 'orga_only' => false],
            ['name' => 'Normal', 'amount_cents' => $base, 'valid_from' => '2027-04-01 00:00:00', 'valid_until' => '2027-06-20 23:59:59', 'orga_only' => false],
            ['name' => 'Nachmeldung', 'amount_cents' => $base + 1500, 'valid_from' => '2027-06-21 00:00:00', 'valid_until' => '2027-06-27 12:00:00', 'orga_only' => true],
        ];

        foreach ($tiers as $tier) {
            PriceTier::updateOrCreate(['race_id' => $race->id, 'name' => $tier['name']], $tier);
        }
    }

    private function seedOptions(Edition $edition): void
    {
        $shirt = RaceOption::updateOrCreate(
            ['edition_id' => $edition->id, 'name' => 'Funktionsshirt'],
            ['price_cents' => 1800, 'quota' => 400, 'sort' => 0, 'is_active' => true],
        );

        foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $sort => $size) {
            $shirt->variants()->updateOrCreate(['name' => $size], ['sort' => $sort]);
        }

        RaceOption::updateOrCreate(
            ['edition_id' => $edition->id, 'name' => 'Pasta-Party'],
            ['price_cents' => 850, 'sort' => 1, 'is_active' => true],
        );

        RaceOption::updateOrCreate(
            ['edition_id' => $edition->id, 'name' => 'Zeitmessungschip-Miete'],
            ['price_cents' => 300, 'sort' => 2, 'is_active' => true],
        );
    }

    private function seedCancellationRules(Edition $edition): void
    {
        $steps = [
            ['valid_until' => '2027-04-30 23:59:59', 'refund_percent' => 100, 'fee_cents' => 500],
            ['valid_until' => '2027-05-31 23:59:59', 'refund_percent' => 50, 'fee_cents' => 0],
        ];

        foreach ($steps as $step) {
            CancellationRule::updateOrCreate(
                ['edition_id' => $edition->id, 'valid_until' => $step['valid_until']],
                $step,
            );
        }
    }
}
