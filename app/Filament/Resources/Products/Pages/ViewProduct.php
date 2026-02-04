<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Packages\PackageResource;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addPackage')
                ->label('Add Package')
                ->icon(Heroicon::Plus)
                ->color('success')
                ->url(fn () => PackageResource::getUrl('create', [
                    'product_id' => $this->record->getKey(),
                ])),
            Action::make('toggleActive')
                ->label(fn () => $this->record->is_active ? 'Deactivate' : 'Activate')
                ->icon(fn () => $this->record->is_active ? Heroicon::XCircle : Heroicon::CheckCircle)
                ->color(fn () => $this->record->is_active ? 'warning' : 'success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['is_active' => ! $this->record->is_active]);
                    Notification::make()
                        ->title($this->record->is_active ? 'Product activated' : 'Product deactivated')
                        ->success()
                        ->send();
                }),
            EditAction::make()
                ->icon(Heroicon::Pencil),
            DeleteAction::make()
                ->icon(Heroicon::Trash),
        ];
    }
}
