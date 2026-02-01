<?php

namespace App\Filament\Resources\NaldaCsvUploads\Pages;

use App\Filament\Resources\NaldaCsvUploads\NaldaCsvUploadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNaldaCsvUploads extends ListRecords
{
    protected static string $resource = NaldaCsvUploadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
