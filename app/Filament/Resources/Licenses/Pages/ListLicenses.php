<?php

namespace App\Filament\Resources\Licenses\Pages;

use App\Enums\LicenseStatus;
use App\Filament\Resources\Licenses\LicenseResource;
use App\Models\License;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListLicenses extends ListRecords
{
    protected static string $resource = LicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::Plus)
                ->label('New License'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Licenses')
                ->badge(License::count())
                ->badgeColor('gray'),
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LicenseStatus::Active))
                ->badge(License::where('status', LicenseStatus::Active)->count())
                ->badgeColor('success')
                ->icon(Heroicon::CheckCircle),
            'paused' => Tab::make('Paused')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LicenseStatus::Paused))
                ->badge(License::where('status', LicenseStatus::Paused)->count())
                ->badgeColor('info')
                ->icon(Heroicon::PauseCircle),
            'suspended' => Tab::make('Suspended')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LicenseStatus::Suspended))
                ->badge(License::where('status', LicenseStatus::Suspended)->count())
                ->badgeColor('warning')
                ->icon(Heroicon::ExclamationTriangle),
            'expired' => Tab::make('Expired')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LicenseStatus::Expired))
                ->badge(License::where('status', LicenseStatus::Expired)->count())
                ->badgeColor('gray')
                ->icon(Heroicon::Clock),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LicenseStatus::Cancelled))
                ->badge(License::where('status', LicenseStatus::Cancelled)->count())
                ->badgeColor('danger')
                ->icon(Heroicon::XCircle),
        ];
    }
}
