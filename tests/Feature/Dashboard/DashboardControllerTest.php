<?php

use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;

it('redirects guests to login', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

it('shows the dashboard to authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/index'));
});

it('shows dashboard stats for user with licenses', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['is_active' => true]);
    $package = Package::factory()->for($product)->create(['is_active' => true]);

    $license = License::factory()
        ->for($user)
        ->for($product)
        ->for($package)
        ->active()
        ->create();

    LicenseActivation::factory()
        ->for($license)
        ->active()
        ->count(2)
        ->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/index')
            ->has('stats')
            ->has('recent_licenses')
            ->has('subscriptions')
        );
});

it('shows the subscriptions page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard/subscriptions')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/subscriptions/index'));
});

it('shows the licenses page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard/licenses')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/licenses/index'));
});

it('shows a specific license', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['is_active' => true]);
    $package = Package::factory()->for($product)->create(['is_active' => true]);

    $license = License::factory()
        ->for($user)
        ->for($product)
        ->for($package)
        ->create();

    $this->actingAs($user)
        ->get("/dashboard/licenses/{$license->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/licenses/show'));
});

it('prevents viewing another users license', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = Product::factory()->create(['is_active' => true]);
    $package = Package::factory()->for($product)->create(['is_active' => true]);

    $license = License::factory()
        ->for($otherUser)
        ->for($product)
        ->for($package)
        ->create();

    $this->actingAs($user)
        ->get("/dashboard/licenses/{$license->id}")
        ->assertForbidden();
});

it('shows the profile page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/profile'));
});

it('shows the settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/settings'));
});

it('updates user profile', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($user)
        ->put('/dashboard/profile', [
            'name' => 'New Name',
            'email' => $user->email,
        ])
        ->assertRedirect();

    expect($user->fresh()->name)->toBe('New Name');
});

it('can deactivate a license activation', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['is_active' => true]);
    $package = Package::factory()->for($product)->create(['is_active' => true]);

    $license = License::factory()
        ->for($user)
        ->for($product)
        ->for($package)
        ->create();

    $activation = LicenseActivation::factory()
        ->for($license)
        ->active()
        ->create();

    $this->actingAs($user)
        ->delete("/dashboard/licenses/{$license->id}/activations/{$activation->id}")
        ->assertRedirect();

    expect($activation->fresh()->deactivated_at)->not->toBeNull();
});
