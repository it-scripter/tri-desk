<?php

namespace App\Domain\Competition\Services;

use App\Domain\Competition\Enums\EditionStatus;
use App\Domain\Competition\Models\CancellationRule;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceOption;
use App\Domain\Competition\Models\RaceOptionVariant;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Support\ActivityLogStatus;

/**
 * Copies an edition from a previous year (specification W-01, B-01): race
 * days, races, price tiers, options and cancellation rules. Org units, posts,
 * texts and export profiles join here once they exist (phases 3, 4, 7).
 *
 * Every date moves by the distance between the old and the new race day, so
 * weekdays and lead times stay the same: a Sunday stays a Sunday, early bird
 * still ends the same number of days before the race. A source without a
 * start date cannot be shifted; its dates stay empty in the copy.
 *
 * Not copied: the note, the display name (a historic name such as BerlinMan
 * belongs to its year), the wk mapping of the legacy results and, later, every
 * registration. The copy starts as planned and never as the current edition.
 */
class EditionCopier
{
    public function copy(
        Edition $source,
        int $year,
        ?int $number,
        string $slug,
        ?CarbonInterface $startsOn,
        ?User $causer = null,
    ): Edition {
        $offset = $source->starts_on !== null && $startsOn !== null
            ? (int) $source->starts_on->copy()->startOfDay()->diffInDays(Carbon::instance($startsOn)->startOfDay(), false)
            : null;

        $shift = fn (?Carbon $date): ?Carbon => $date !== null && $offset !== null
            ? $date->copy()->addDays($offset)
            : null;

        // One copy is one change, so the audit log gets a single entry naming
        // the source instead of one "created" entry per copied row.
        $copy = $this->withoutAudit(fn (): Edition => DB::transaction(function () use ($source, $year, $number, $slug, $shift): Edition {
            $copy = Edition::create([
                'competition_id' => $source->competition_id,
                'year' => $year,
                'number' => $number,
                'slug' => $slug,
                'status' => EditionStatus::Planned,
                'is_current' => false,
                'starts_on' => $shift($source->starts_on),
                'ends_on' => $shift($source->ends_on),
                'registration_opens_at' => $shift($source->registration_opens_at),
                'registration_closes_at' => $shift($source->registration_closes_at),
                'transfer_deadline_at' => $shift($source->transfer_deadline_at),
            ]);

            $dayIds = $this->copyDays($source, $copy, $shift);
            $raceIds = $this->copyRaces($source, $copy, $dayIds, $shift);
            $this->copyOptions($source, $copy, $raceIds);
            $this->copyCancellationRules($source, $copy, $shift);

            return $copy;
        }));

        activity('audit')
            ->event('copied')
            ->performedOn($copy)
            ->causedBy($causer)
            ->withProperties([
                'source_edition_id' => $source->id,
                'source' => $source->titleWithNumber(),
                'day_offset' => $offset,
            ])
            ->log(__('audit.events.copied_from', ['source' => $source->titleWithNumber()]));

        return $copy;
    }

    /**
     * @param  callable(?Carbon): ?Carbon  $shift
     * @return array<int, int> old day id => new day id
     */
    private function copyDays(Edition $source, Edition $copy, callable $shift): array
    {
        $ids = [];

        foreach ($source->days as $day) {
            $new = $day->replicate(['edition_id']);
            $new->edition_id = $copy->id;
            $new->date = $shift($day->date);
            $new->save();

            $ids[$day->id] = $new->id;
        }

        return $ids;
    }

    /**
     * @param  array<int, int>  $dayIds
     * @param  callable(?Carbon): ?Carbon  $shift
     * @return array<int, int> old race id => new race id
     */
    private function copyRaces(Edition $source, Edition $copy, array $dayIds, callable $shift): array
    {
        $ids = [];

        foreach ($source->races()->with('priceTiers')->get() as $race) {
            /** @var Race $new */
            $new = $race->replicate(['edition_id', 'edition_day_id']);
            $new->edition_id = $copy->id;
            $new->edition_day_id = $race->edition_day_id !== null ? ($dayIds[$race->edition_day_id] ?? null) : null;
            $new->save();

            foreach ($race->priceTiers as $tier) {
                /** @var PriceTier $newTier */
                $newTier = $tier->replicate(['race_id']);
                $newTier->race_id = $new->id;
                $newTier->valid_from = $shift($tier->valid_from);
                $newTier->valid_until = $shift($tier->valid_until);
                $newTier->save();
            }

            $ids[$race->id] = $new->id;
        }

        return $ids;
    }

    /**
     * Restrictions to races point at the copied races, never at the old ones.
     *
     * @param  array<int, int>  $raceIds
     */
    private function copyOptions(Edition $source, Edition $copy, array $raceIds): void
    {
        foreach ($source->raceOptions()->with(['variants', 'races'])->get() as $option) {
            /** @var RaceOption $new */
            $new = $option->replicate(['edition_id']);
            $new->edition_id = $copy->id;
            $new->save();

            foreach ($option->variants as $variant) {
                /** @var RaceOptionVariant $newVariant */
                $newVariant = $variant->replicate(['race_option_id']);
                $newVariant->race_option_id = $new->id;
                $newVariant->save();
            }

            $new->races()->attach(
                $option->races->map(fn (Race $race): ?int => $raceIds[$race->id] ?? null)->filter()->values()->all(),
            );
        }
    }

    /**
     * @param  callable(?Carbon): ?Carbon  $shift
     */
    private function copyCancellationRules(Edition $source, Edition $copy, callable $shift): void
    {
        foreach ($source->cancellationRules as $rule) {
            $validUntil = $shift($rule->valid_until);

            // A step without a deadline would refund forever; without a
            // shift there is no sensible deadline, so the step is left out.
            if ($validUntil === null) {
                continue;
            }

            /** @var CancellationRule $new */
            $new = $rule->replicate(['edition_id']);
            $new->edition_id = $copy->id;
            $new->valid_until = $validUntil;
            $new->save();
        }
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withoutAudit(callable $callback): mixed
    {
        $status = app(ActivityLogStatus::class);
        $wasEnabled = ! $status->disabled();

        $status->disable();

        try {
            return $callback();
        } finally {
            if ($wasEnabled) {
                $status->enable();
            }
        }
    }
}
