<?php

namespace App\Filament\Resources\Licenses\Pages;

use App\Enums\LicenseStatus;
use App\Filament\Resources\Licenses\LicenseResource;
use App\Models\License;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewLicense extends ViewRecord
{
    protected static string $resource = LicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copyLicenseKey')
                ->label('Copy Key')
                ->icon(Heroicon::Clipboard)
                ->color('gray')
                ->action(function () {
                    Notification::make()
                        ->title('License key copied to clipboard!')
                        ->success()
                        ->send();
                })
                ->extraAttributes([
                    'x-data' => '',
                    'x-on:click' => 'navigator.clipboard.writeText(\''.$this->record->license_key.'\')',
                ]),
            Action::make('suspend')
                ->label('Suspend')
                ->icon(Heroicon::PauseCircle)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Suspend License')
                ->modalDescription('This will suspend the license and prevent it from being used. Are you sure?')
                ->action(function (License $record) {
                    $record->update(['status' => LicenseStatus::Suspended]);
                    Notification::make()
                        ->title('License suspended')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status === LicenseStatus::Active),
            Action::make('activate')
                ->label('Activate')
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Activate License')
                ->modalDescription('This will activate the license and allow it to be used.')
                ->action(function (License $record) {
                    $record->update(['status' => LicenseStatus::Active]);
                    Notification::make()
                        ->title('License activated')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status !== LicenseStatus::Active),
            EditAction::make()
                ->icon(Heroicon::Pencil),
            DeleteAction::make()
                ->icon(Heroicon::Trash),
        ];
    }
}
