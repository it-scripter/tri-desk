<?php

namespace App\Filament\Orga\Resources\Editions\Pages;

use App\Filament\Orga\Resources\Editions\EditionResource;
use Filament\Resources\Pages\ListRecords;

class ListEditions extends ListRecords
{
    protected static string $resource = EditionResource::class;

    /**
     * Editions are created by the organiser, not by the orga team.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
