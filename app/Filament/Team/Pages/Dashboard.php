<?php

namespace App\Filament\Team\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Placeholder for the team leader panel. Posts, daily staffing and volunteer
 * assignment arrive with the volunteer module (specification H-10 to H-27).
 */
class Dashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected string $view = 'filament.team.dashboard';

    public function getTitle(): string
    {
        return __('panel.team.heading');
    }
}
