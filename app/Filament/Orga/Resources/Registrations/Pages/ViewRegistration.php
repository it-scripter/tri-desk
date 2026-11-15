<?php

namespace App\Filament\Orga\Resources\Registrations\Pages;

use App\Filament\Orga\Resources\Registrations\Actions\CancelRegistrationAction;
use App\Filament\Orga\Resources\Registrations\RegistrationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRegistration extends ViewRecord
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CancelRegistrationAction::make(),
        ];
    }
}
