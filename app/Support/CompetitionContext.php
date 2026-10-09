<?php

namespace App\Support;

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use RuntimeException;

/**
 * Holds the competition the current request belongs to, plus its current
 * edition (specification C-01). Registered as a singleton and filled by the
 * ResolveCompetitionContext middleware, so controllers, views and services do
 * not have to pass the competition around.
 */
class CompetitionContext
{
    private ?Competition $competition = null;

    private ?Edition $edition = null;

    private bool $editionResolved = false;

    public function set(Competition $competition): void
    {
        $this->competition = $competition;
        $this->edition = null;
        $this->editionResolved = false;
    }

    public function has(): bool
    {
        return $this->competition !== null;
    }

    public function competition(): ?Competition
    {
        return $this->competition;
    }

    /**
     * The competition of this request, or an exception. Use in code that can
     * only run inside the competition context.
     */
    public function requireCompetition(): Competition
    {
        return $this->competition
            ?? throw new RuntimeException('Es ist kein Wettkampf-Kontext gesetzt.');
    }

    /**
     * The edition the public pages default to. Resolved lazily and cached for
     * the request, because not every page needs it.
     */
    public function edition(): ?Edition
    {
        if (! $this->editionResolved) {
            $this->edition = $this->competition?->currentEdition();
            $this->editionResolved = true;
        }

        return $this->edition;
    }

    public function requireEdition(): Edition
    {
        return $this->edition()
            ?? throw new RuntimeException('Der Wettkampf hat keine aktuelle Ausgabe.');
    }

    /**
     * Overrides the lazily resolved edition, e.g. when a route addresses a
     * specific past edition rather than the current one.
     */
    public function setEdition(?Edition $edition): void
    {
        $this->edition = $edition;
        $this->editionResolved = true;
    }
}
