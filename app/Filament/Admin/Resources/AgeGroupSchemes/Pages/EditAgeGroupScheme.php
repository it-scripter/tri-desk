<?php

namespace App\Filament\Admin\Resources\AgeGroupSchemes\Pages;

use App\Filament\Admin\Resources\AgeGroupSchemes\AgeGroupSchemeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAgeGroupScheme extends EditRecord
{
    protected static string $resource = AgeGroupSchemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
