<?php

namespace App\Filament\Orga\Resources\Editions\Pages;

use App\Filament\Orga\Resources\Editions\Actions\TestRegistrationAction;
use App\Filament\Orga\Resources\Editions\EditionResource;
use Filament\Resources\Pages\EditRecord;

class EditEdition extends EditRecord
{
    protected static string $resource = EditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            TestRegistrationAction::make(),
        ];
    }
}
