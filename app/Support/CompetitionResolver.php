<?php

namespace App\Support;

use App\Domain\Competition\Models\Competition;
use Illuminate\Http\Request;

/**
 * Finds the competition a request belongs to.
 *
 * Today the slug in the path decides (/volkstriathlon/...). The host is
 * checked first already, so moving a competition onto its own domain or
 * subdomain later (specification C-01) only needs a filled domain column - no
 * change to routes, middleware or views.
 */
class CompetitionResolver
{
    public function resolve(Request $request, ?string $slug = null): ?Competition
    {
        return $this->resolveFromHost($request) ?? $this->resolveFromSlug($slug);
    }

    public function resolveFromHost(Request $request): ?Competition
    {
        $host = $request->getHost();

        if ($host === '') {
            return null;
        }

        return Competition::query()
            ->where('is_active', true)
            ->where('domain', $host)
            ->first();
    }

    public function resolveFromSlug(?string $slug): ?Competition
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        return Competition::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->first();
    }
}
