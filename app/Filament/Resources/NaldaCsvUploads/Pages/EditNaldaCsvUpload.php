<?php

namespace App\Filament\Resources\NaldaCsvUploads\Pages;

use App\Filament\Resources\NaldaCsvUploads\NaldaCsvUploadResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNaldaCsvUpload extends EditRecord
{
    protected static string $resource = NaldaCsvUploadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
