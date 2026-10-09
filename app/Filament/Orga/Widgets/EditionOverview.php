<?php

namespace App\Filament\Orga\Widgets;

use App\Domain\Competition\Models\Edition;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard of the edition (specification 6.3). Registration and payment
 * figures follow in phase 2, volunteer coverage in phase 5 - for now it shows
 * what the foundation knows.
 */
class EditionOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $editions = Edition::query()
            ->visibleTo($user)
            ->with('competition')
            ->withCount(['races', 'days'])
            ->orderByDesc('is_current')
            ->orderByDesc('year')
            ->get();

        if ($editions->isEmpty()) {
            return [
                Stat::make(__('panel.orga.overview.none'), '-')
                    ->description(__('panel.orga.overview.none_description')),
            ];
        }

        $current = $editions->firstWhere('is_current', true) ?? $editions->first();

        return [
            Stat::make(__('panel.orga.overview.heading'), $current->titleWithNumber())
                ->description($current->status->label()),

            Stat::make(__('panel.orga.overview.races'), (string) $current->races_count),

            Stat::make(__('panel.orga.overview.days'), (string) $current->days_count),

            Stat::make(__('panel.orga.overview.editions'), (string) $editions->count()),
        ];
    }
}
