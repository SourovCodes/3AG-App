<?php

use App\Filament\Resources\Packages\PackageResource;
use App\Filament\Resources\Packages\Pages\CreatePackage;
use App\Filament\Resources\Packages\Pages\EditPackage;
use App\Filament\Resources\Packages\Pages\ListPackages;
use App\Filament\Resources\Packages\Pages\ViewPackage;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['email' => 'sourovcodes@gmail.com']);
    $this->actingAs($this->admin);
});

describe('List Packages Page', function () {
    it('can render the index page', function () {
        $this->get(PackageResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can list packages', function () {
        $packages = Package::factory()->count(3)->create();

        Livewire::test(ListPackages::class)
            ->assertCanSeeTableRecords($packages);
    });

    it('can search packages by name', function () {
        $packageToFind = Package::factory()->create(['name' => 'Premium Package']);
        $otherPackage = Package::factory()->create(['name' => 'Basic Package']);

        Livewire::test(ListPackages::class)
            ->searchTable('Premium')
            ->assertCanSeeTableRecords([$packageToFind])
            ->assertCanNotSeeTableRecords([$otherPackage]);
    });

    it('can filter packages by product', function () {
        $product = Product::factory()->create();
        $packageWithProduct = Package::factory()->create(['product_id' => $product->id]);
        $otherPackage = Package::factory()->create();

        Livewire::test(ListPackages::class)
            ->filterTable('product', $product->id)
            ->assertCanSeeTableRecords([$packageWithProduct])
            ->assertCanNotSeeTableRecords([$otherPackage]);
    });

    it('can filter packages by active status', function () {
        $activePackage = Package::factory()->create(['is_active' => true]);
        $inactivePackage = Package::factory()->inactive()->create();

        Livewire::test(ListPackages::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$activePackage])
            ->assertCanNotSeeTableRecords([$inactivePackage]);
    });

    it('can sort packages by price', function () {
        $cheapPackage = Package::factory()->create(['monthly_price' => 10.00]);
        $expensivePackage = Package::factory()->create(['monthly_price' => 100.00]);

        Livewire::test(ListPackages::class)
            ->sortTable('monthly_price')
            ->assertCanSeeTableRecords([$cheapPackage, $expensivePackage], inOrder: true);
    });
});

describe('Create Package Page', function () {
    it('can render the create page', function () {
        $this->get(PackageResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can create a package', function () {
        $product = Product::factory()->create();

        Livewire::test(CreatePackage::class)
            ->fillForm([
                'product_id' => $product->id,
                'name' => 'New Package',
                'slug' => 'new-package',
                'description' => 'A new package description',
                'domain_limit' => 5,
                'monthly_price' => 29.99,
                'yearly_price' => 299.99,
                'stripe_monthly_price_id' => 'price_monthly123',
                'stripe_yearly_price_id' => 'price_yearly123',
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Package::class, [
            'product_id' => $product->id,
            'name' => 'New Package',
            'slug' => 'new-package',
            'domain_limit' => 5,
        ]);
    });

    it('validates required fields', function () {
        Livewire::test(CreatePackage::class)
            ->fillForm([
                'product_id' => null,
                'name' => '',
                'slug' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['product_id', 'name', 'slug']);
    });

    it('validates unique slug per product', function () {
        $product = Product::factory()->create();
        Package::factory()->create(['product_id' => $product->id, 'slug' => 'existing-slug']);

        Livewire::test(CreatePackage::class)
            ->fillForm([
                'product_id' => $product->id,
                'name' => 'Test Package',
                'slug' => 'existing-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    });

    it('can create unlimited domain package', function () {
        $product = Product::factory()->create();

        Livewire::test(CreatePackage::class)
            ->fillForm([
                'product_id' => $product->id,
                'name' => 'Unlimited Package',
                'slug' => 'unlimited-package',
                'domain_limit' => null,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $package = Package::where('slug', 'unlimited-package')->first();
        expect($package->domain_limit)->toBeNull();
    });
});

describe('View Package Page', function () {
    it('can render the view page', function () {
        $package = Package::factory()->create();

        $this->get(PackageResource::getUrl('view', ['record' => $package]))
            ->assertSuccessful();
    });

    it('displays package information correctly', function () {
        $package = Package::factory()->create([
            'name' => 'View Test Package',
            'monthly_price' => 49.99,
        ]);

        Livewire::test(ViewPackage::class, ['record' => $package->getRouteKey()])
            ->assertSee('View Test Package');
    });

    it('displays product relationship', function () {
        $product = Product::factory()->create(['name' => 'Parent Product']);
        $package = Package::factory()->create(['product_id' => $product->id]);

        Livewire::test(ViewPackage::class, ['record' => $package->getRouteKey()])
            ->assertSuccessful();
    });
});

describe('Edit Package Page', function () {
    it('can render the edit page', function () {
        $package = Package::factory()->create();

        $this->get(PackageResource::getUrl('edit', ['record' => $package]))
            ->assertSuccessful();
    });

    it('can retrieve package data', function () {
        $package = Package::factory()->create([
            'name' => 'Original Package',
            'slug' => 'original-package',
            'monthly_price' => 39.99,
            'domain_limit' => 10,
        ]);

        Livewire::test(EditPackage::class, ['record' => $package->getRouteKey()])
            ->assertFormSet([
                'name' => 'Original Package',
                'slug' => 'original-package',
                'domain_limit' => 10,
            ]);
    });

    it('can update a package', function () {
        $package = Package::factory()->create();

        Livewire::test(EditPackage::class, ['record' => $package->getRouteKey()])
            ->fillForm([
                'name' => 'Updated Package Name',
                'monthly_price' => 59.99,
                'domain_limit' => 25,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $package->refresh();

        expect($package->name)->toBe('Updated Package Name')
            ->and($package->domain_limit)->toBe(25);
    });

    it('can update pricing', function () {
        $package = Package::factory()->create([
            'monthly_price' => 29.99,
            'yearly_price' => 299.99,
        ]);

        Livewire::test(EditPackage::class, ['record' => $package->getRouteKey()])
            ->fillForm([
                'monthly_price' => 49.99,
                'yearly_price' => 499.99,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $package->refresh();

        expect((float) $package->monthly_price)->toEqual(49.99)
            ->and((float) $package->yearly_price)->toEqual(499.99);
    });

    it('can toggle package active status', function () {
        $package = Package::factory()->create(['is_active' => true]);

        Livewire::test(EditPackage::class, ['record' => $package->getRouteKey()])
            ->fillForm([
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $package->refresh();
        expect($package->is_active)->toBeFalse();
    });

    it('can delete a package', function () {
        $package = Package::factory()->create();

        Livewire::test(EditPackage::class, ['record' => $package->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($package);
    });
});

describe('Package Features', function () {
    it('can create package with features', function () {
        $product = Product::factory()->create();

        Livewire::test(CreatePackage::class)
            ->fillForm([
                'product_id' => $product->id,
                'name' => 'Featured Package',
                'slug' => 'featured-package',
                'features' => [
                    'Feature One',
                    'Feature Two',
                    'Feature Three',
                ],
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $package = Package::where('slug', 'featured-package')->first();
        expect($package->features)->toHaveCount(3)
            ->and($package->features[0])->toBe('Feature One');
    });
});
