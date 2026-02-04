<?php

namespace App\Filament\Widgets;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Product;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $newUsersThisMonth = User::where('created_at', '>=', now()->startOfMonth())->count();
        $newUsersLastMonth = User::whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth(),
        ])->count();

        $activeLicenses = License::where('status', LicenseStatus::Active)->count();
        $totalLicenses = License::count();

        $activeProducts = Product::where('is_active', true)->count();

        $usersWithSubscription = User::whereNotNull('stripe_id')->count();

        return [
            Stat::make('Total Users', Number::format($totalUsers))
                ->description($newUsersThisMonth.' new this month')
                ->descriptionIcon(Heroicon::ArrowTrendingUp)
                ->chart($this->getUserTrendChart())
                ->color($newUsersThisMonth >= $newUsersLastMonth ? 'success' : 'danger'),

            Stat::make('Active Licenses', Number::format($activeLicenses))
                ->description(Number::format($totalLicenses).' total licenses')
                ->descriptionIcon(Heroicon::Key)
                ->chart($this->getLicenseTrendChart())
                ->color('success'),

            Stat::make('Paying Customers', Number::format($usersWithSubscription))
                ->description($totalUsers > 0 ? round(($usersWithSubscription / $totalUsers) * 100, 1).'% conversion' : '0% conversion')
                ->descriptionIcon(Heroicon::CreditCard)
                ->color('info'),

            Stat::make('Active Products', Number::format($activeProducts))
                ->description('Available for purchase')
                ->descriptionIcon(Heroicon::CubeTransparent)
                ->color('warning'),
        ];
    }

    /**
     * @return array<int>
     */
    protected function getUserTrendChart(): array
    {
        return collect(range(6, 0))->map(function ($month) {
            $date = now()->subMonths($month);

            return User::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        })->toArray();
    }

    /**
     * @return array<int>
     */
    protected function getLicenseTrendChart(): array
    {
        return collect(range(6, 0))->map(function ($month) {
            $date = now()->subMonths($month);

            return License::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        })->toArray();
    }
}
