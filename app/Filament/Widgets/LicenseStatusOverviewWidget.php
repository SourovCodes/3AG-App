<?php

namespace App\Filament\Widgets;

use App\Enums\LicenseStatus;
use App\Models\License;
use Filament\Widgets\ChartWidget;

class LicenseStatusOverviewWidget extends ChartWidget
{
    protected ?string $heading = 'Licenses by Status';

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '60s';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $statuses = collect(LicenseStatus::cases());

        $counts = $statuses->mapWithKeys(function (LicenseStatus $status) {
            return [$status->value => License::where('status', $status)->count()];
        });

        return [
            'datasets' => [
                [
                    'data' => $counts->values()->toArray(),
                    'backgroundColor' => $statuses->map(fn (LicenseStatus $status) => match ($status->getColor()) {
                        'success' => 'rgb(16, 185, 129)',
                        'info' => 'rgb(14, 165, 233)',
                        'warning' => 'rgb(245, 158, 11)',
                        'gray' => 'rgb(107, 114, 128)',
                        'danger' => 'rgb(239, 68, 68)',
                        default => 'rgb(156, 163, 175)',
                    })->toArray(),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $statuses->map(fn (LicenseStatus $status) => $status->getLabel())->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
