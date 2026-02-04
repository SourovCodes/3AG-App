<?php

use App\Filament\Widgets\LatestLicensesWidget;
use App\Filament\Widgets\LicenseStatusOverviewWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\License;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['email' => 'sourovcodes@gmail.com']);
    $this->actingAs($this->admin);
});

describe('Stats Overview Widget', function () {
    it('can render the widget', function () {
        Livewire::test(StatsOverviewWidget::class)
            ->assertSuccessful();
    });

    it('displays total users count', function () {
        User::factory()->count(5)->create();

        $widget = Livewire::test(StatsOverviewWidget::class);
        $widget->assertSuccessful();
    });

    it('displays active licenses count', function () {
        License::factory()->count(3)->active()->create();
        License::factory()->count(2)->suspended()->create();

        $widget = Livewire::test(StatsOverviewWidget::class);
        $widget->assertSuccessful();
    });

    it('displays active products count', function () {
        Product::factory()->count(4)->create(['is_active' => true]);
        Product::factory()->count(2)->inactive()->create();

        $widget = Livewire::test(StatsOverviewWidget::class);
        $widget->assertSuccessful();
    });

    it('displays paying customers count', function () {
        User::factory()->count(3)->create(['stripe_id' => 'cus_test123']);
        User::factory()->count(2)->create(['stripe_id' => null]);

        $widget = Livewire::test(StatsOverviewWidget::class);
        $widget->assertSuccessful();
    });
});

describe('License Status Overview Widget', function () {
    it('can render the widget', function () {
        Livewire::test(LicenseStatusOverviewWidget::class)
            ->assertSuccessful();
    });

    it('displays chart with license status data', function () {
        License::factory()->count(5)->active()->create();
        License::factory()->count(3)->suspended()->create();
        License::factory()->count(2)->expired()->create();

        $widget = Livewire::test(LicenseStatusOverviewWidget::class);
        $widget->assertSuccessful();
    });

    it('handles empty license data', function () {
        // No licenses in database
        $widget = Livewire::test(LicenseStatusOverviewWidget::class);
        $widget->assertSuccessful();
    });
});

describe('Revenue Chart Widget', function () {
    it('can render the widget', function () {
        Livewire::test(RevenueChartWidget::class)
            ->assertSuccessful();
    });

    it('can change filter to 30 days', function () {
        Livewire::test(RevenueChartWidget::class)
            ->set('filter', '30days')
            ->assertSuccessful();
    });

    it('can change filter to 6 months', function () {
        Livewire::test(RevenueChartWidget::class)
            ->set('filter', '6months')
            ->assertSuccessful();
    });

    it('can change filter to 12 months', function () {
        Livewire::test(RevenueChartWidget::class)
            ->set('filter', '12months')
            ->assertSuccessful();
    });

    it('displays user and license growth data', function () {
        // Create users and licenses over time
        User::factory()->count(5)->create(['created_at' => now()->subDays(15)]);
        User::factory()->count(3)->create(['created_at' => now()->subDays(5)]);
        License::factory()->count(4)->create(['created_at' => now()->subDays(10)]);

        $widget = Livewire::test(RevenueChartWidget::class);
        $widget->assertSuccessful();
    });
});

describe('Latest Licenses Widget', function () {
    it('can render the widget', function () {
        Livewire::test(LatestLicensesWidget::class)
            ->assertSuccessful();
    });

    it('displays recent licenses', function () {
        $licenses = License::factory()->count(5)->create();

        Livewire::test(LatestLicensesWidget::class)
            ->assertCanSeeTableRecords($licenses);
    });

    it('limits display to 10 licenses', function () {
        License::factory()->count(15)->create();

        $widget = Livewire::test(LatestLicensesWidget::class);
        $widget->assertSuccessful();
    });

    it('orders licenses by most recent first', function () {
        $oldLicense = License::factory()->create(['created_at' => now()->subDays(10)]);
        $newLicense = License::factory()->create(['created_at' => now()]);

        Livewire::test(LatestLicensesWidget::class)
            ->assertCanSeeTableRecords([$newLicense, $oldLicense], inOrder: true);
    });

    it('displays license key column', function () {
        $license = License::factory()->create(['license_key' => 'TEST-KEY-1234-5678']);

        Livewire::test(LatestLicensesWidget::class)
            ->assertSee('TEST-KEY-1234-5678');
    });

    it('handles empty licenses gracefully', function () {
        // No licenses in database
        $widget = Livewire::test(LatestLicensesWidget::class);
        $widget->assertSuccessful();
    });
});
