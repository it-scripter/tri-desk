<?php

namespace App\Filament\Orga\Resources\Races\Pages;

use App\Filament\Orga\Resources\Races\RaceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRace extends EditRecord
{
    protected static string $resource = RaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
