<?php

namespace App\Filament\Admin\Resources\AgeGroupSchemes\Pages;

use App\Filament\Admin\Resources\AgeGroupSchemes\AgeGroupSchemeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAgeGroupSchemes extends ListRecords
{
    protected static string $resource = AgeGroupSchemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
