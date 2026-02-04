<?php

namespace App\Filament\Widgets;

use App\Models\License;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    protected ?string $heading = 'Growth Over Time';

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = '120s';

    protected ?string $maxHeight = '280px';

    public ?string $filter = '6months';

    protected function getFilters(): ?array
    {
        return [
            '30days' => 'Last 30 days',
            '6months' => 'Last 6 months',
            '12months' => 'Last 12 months',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter;

        [$startDate, $labels] = match ($filter) {
            '30days' => [now()->subDays(29), collect(range(29, 0))->map(fn ($day) => now()->subDays($day)->format('M d'))],
            '6months' => [now()->subMonths(5)->startOfMonth(), collect(range(5, 0))->map(fn ($month) => now()->subMonths($month)->format('M Y'))],
            '12months' => [now()->subMonths(11)->startOfMonth(), collect(range(11, 0))->map(fn ($month) => now()->subMonths($month)->format('M Y'))],
            default => [now()->subMonths(5)->startOfMonth(), collect(range(5, 0))->map(fn ($month) => now()->subMonths($month)->format('M Y'))],
        };

        $usersData = $this->getDataByPeriod(User::class, $startDate, $filter);
        $licensesData = $this->getDataByPeriod(License::class, $startDate, $filter);

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $usersData,
                    'borderColor' => 'rgb(14, 165, 233)',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'New Licenses',
                    'data' => $licensesData,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }

    /**
     * @return array<int>
     */
    protected function getDataByPeriod(string $model, \DateTimeInterface $startDate, string $filter): array
    {
        if ($filter === '30days') {
            return collect(range(29, 0))->map(function ($day) use ($model) {
                $date = now()->subDays($day);

                return $model::whereDate('created_at', $date)->count();
            })->toArray();
        }

        $months = $filter === '12months' ? 12 : 6;

        return collect(range($months - 1, 0))->map(function ($month) use ($model) {
            $date = now()->subMonths($month);

            return $model::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        })->toArray();
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
