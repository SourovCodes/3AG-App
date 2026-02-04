<?php

use App\Enums\LicenseStatus;
use App\Filament\Resources\Licenses\Pages\ViewLicense;
use App\Filament\Resources\Licenses\RelationManagers\ActivationsRelationManager;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\RelationManagers\PackagesRelationManager;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\LicensesRelationManager;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['email' => 'sourovcodes@gmail.com']);
    $this->actingAs($this->admin);
});

describe('Licenses Relation Manager (on User)', function () {
    it('can render the relation manager', function () {
        $user = User::factory()->create();

        Livewire::test(LicensesRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => ViewUser::class,
        ])->assertSuccessful();
    });

    it('can list user licenses', function () {
        $user = User::factory()->create();
        $licenses = License::factory()->count(3)->create(['user_id' => $user->id]);

        Livewire::test(LicensesRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => ViewUser::class,
        ])->assertCanSeeTableRecords($licenses);
    });

    it('only shows licenses for the owner user', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userLicense = License::factory()->create(['user_id' => $user->id]);
        $otherLicense = License::factory()->create(['user_id' => $otherUser->id]);

        Livewire::test(LicensesRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => ViewUser::class,
        ])
            ->assertCanSeeTableRecords([$userLicense])
            ->assertCanNotSeeTableRecords([$otherLicense]);
    });

    it('can filter licenses by status', function () {
        $user = User::factory()->create();
        $activeLicense = License::factory()->active()->create(['user_id' => $user->id]);
        $suspendedLicense = License::factory()->suspended()->create(['user_id' => $user->id]);

        Livewire::test(LicensesRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => ViewUser::class,
        ])
            ->filterTable('status', LicenseStatus::Active->value)
            ->assertCanSeeTableRecords([$activeLicense])
            ->assertCanNotSeeTableRecords([$suspendedLicense]);
    });
});

describe('Activations Relation Manager (on License)', function () {
    it('can render the relation manager', function () {
        $license = License::factory()->create();

        Livewire::test(ActivationsRelationManager::class, [
            'ownerRecord' => $license,
            'pageClass' => ViewLicense::class,
        ])->assertSuccessful();
    });

    it('can list license activations', function () {
        $license = License::factory()->create();
        $activations = LicenseActivation::factory()->count(3)->create(['license_id' => $license->id]);

        Livewire::test(ActivationsRelationManager::class, [
            'ownerRecord' => $license,
            'pageClass' => ViewLicense::class,
        ])->assertCanSeeTableRecords($activations);
    });

    it('only shows activations for the owner license', function () {
        $license = License::factory()->create();
        $otherLicense = License::factory()->create();

        $licenseActivation = LicenseActivation::factory()->create(['license_id' => $license->id]);
        $otherActivation = LicenseActivation::factory()->create(['license_id' => $otherLicense->id]);

        Livewire::test(ActivationsRelationManager::class, [
            'ownerRecord' => $license,
            'pageClass' => ViewLicense::class,
        ])
            ->assertCanSeeTableRecords([$licenseActivation])
            ->assertCanNotSeeTableRecords([$otherActivation]);
    });

    it('can filter activations by status', function () {
        $license = License::factory()->create();
        $activeActivation = LicenseActivation::factory()->active()->create(['license_id' => $license->id]);
        $deactivatedActivation = LicenseActivation::factory()->deactivated()->create(['license_id' => $license->id]);

        Livewire::test(ActivationsRelationManager::class, [
            'ownerRecord' => $license,
            'pageClass' => ViewLicense::class,
        ])
            ->filterTable('deactivated_at', false)
            ->assertCanSeeTableRecords([$activeActivation])
            ->assertCanNotSeeTableRecords([$deactivatedActivation]);
    });

    it('can search activations by domain', function () {
        $license = License::factory()->create();
        $targetActivation = LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'search-this.example.com',
        ]);
        $otherActivation = LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'different.example.com',
        ]);

        Livewire::test(ActivationsRelationManager::class, [
            'ownerRecord' => $license,
            'pageClass' => ViewLicense::class,
        ])
            ->searchTable('search-this')
            ->assertCanSeeTableRecords([$targetActivation])
            ->assertCanNotSeeTableRecords([$otherActivation]);
    });
});

describe('Packages Relation Manager (on Product)', function () {
    it('can render the relation manager', function () {
        $product = Product::factory()->create();

        Livewire::test(PackagesRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => ViewProduct::class,
        ])->assertSuccessful();
    });

    it('can list product packages', function () {
        $product = Product::factory()->create();
        $packages = Package::factory()->count(3)->create(['product_id' => $product->id]);

        Livewire::test(PackagesRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => ViewProduct::class,
        ])->assertCanSeeTableRecords($packages);
    });

    it('only shows packages for the owner product', function () {
        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();

        $productPackage = Package::factory()->create(['product_id' => $product->id]);
        $otherPackage = Package::factory()->create(['product_id' => $otherProduct->id]);

        Livewire::test(PackagesRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => ViewProduct::class,
        ])
            ->assertCanSeeTableRecords([$productPackage])
            ->assertCanNotSeeTableRecords([$otherPackage]);
    });

    it('can filter packages by active status', function () {
        $product = Product::factory()->create();
        $activePackage = Package::factory()->create(['product_id' => $product->id, 'is_active' => true]);
        $inactivePackage = Package::factory()->inactive()->create(['product_id' => $product->id]);

        Livewire::test(PackagesRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => ViewProduct::class,
        ])
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$activePackage])
            ->assertCanNotSeeTableRecords([$inactivePackage]);
    });

    it('can search packages by name', function () {
        $product = Product::factory()->create();
        $targetPackage = Package::factory()->create([
            'product_id' => $product->id,
            'name' => 'Premium Tier',
        ]);
        $otherPackage = Package::factory()->create([
            'product_id' => $product->id,
            'name' => 'Basic Tier',
        ]);

        Livewire::test(PackagesRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => ViewProduct::class,
        ])
            ->searchTable('Premium')
            ->assertCanSeeTableRecords([$targetPackage])
            ->assertCanNotSeeTableRecords([$otherPackage]);
    });
});
