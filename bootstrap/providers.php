<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\OrgaPanelProvider;
use App\Providers\Filament\TeamPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    OrgaPanelProvider::class,
    TeamPanelProvider::class,
];
