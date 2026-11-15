<?php

namespace App\Filament\Admin\Resources\Editions\Pages;

use App\Filament\Admin\Resources\Editions\Actions\CopyEditionAction;
use App\Filament\Admin\Resources\Editions\EditionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEdition extends EditRecord
{
    protected static string $resource = EditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CopyEditionAction::make(),
            DeleteAction::make(),
        ];
    }
}
