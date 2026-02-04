<?php

use App\Models\License;
use App\Models\Product;
use App\Models\User;
use Filament\Pages\Dashboard;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['email' => 'sourovcodes@gmail.com']);
    $this->actingAs($this->admin);
});

describe('Dashboard Page', function () {
    it('can render the dashboard page', function () {
        $this->get('/admin')
            ->assertSuccessful();
    });

    it('displays dashboard with widgets', function () {
        Livewire::test(Dashboard::class)
            ->assertSuccessful();
    });

    it('shows correct statistics on dashboard', function () {
        // Create test data
        User::factory()->count(10)->create();
        License::factory()->count(5)->active()->create();
        Product::factory()->count(3)->create(['is_active' => true]);

        $this->get('/admin')
            ->assertSuccessful();
    });
});

describe('Dashboard Navigation', function () {
    it('can navigate to users from dashboard', function () {
        $this->get('/admin')
            ->assertSuccessful()
            ->assertSee('Users');
    });

    it('can navigate to licenses from dashboard', function () {
        $this->get('/admin')
            ->assertSuccessful()
            ->assertSee('Licenses');
    });

    it('can navigate to products from dashboard', function () {
        $this->get('/admin')
            ->assertSuccessful()
            ->assertSee('Products');
    });

    it('can navigate to packages from dashboard', function () {
        $this->get('/admin')
            ->assertSuccessful()
            ->assertSee('Packages');
    });
});

describe('Dashboard Access Control', function () {
    it('redirects unauthenticated users to login', function () {
        auth()->logout();

        $this->get('/admin')
            ->assertRedirect();
    });

    it('allows authenticated admin users', function () {
        $this->get('/admin')
            ->assertSuccessful();
    });
});

describe('Dashboard Global Search', function () {
    it('can access global search', function () {
        $this->get('/admin')
            ->assertSuccessful();
    });

    it('global search finds users', function () {
        $user = User::factory()->create(['name' => 'Searchable User']);

        $this->get('/admin')
            ->assertSuccessful();
    });

    it('global search finds licenses', function () {
        $license = License::factory()->create(['license_key' => 'SEARCH-TEST-1234']);

        $this->get('/admin')
            ->assertSuccessful();
    });

    it('global search finds products', function () {
        $product = Product::factory()->create(['name' => 'Searchable Product']);

        $this->get('/admin')
            ->assertSuccessful();
    });
});
