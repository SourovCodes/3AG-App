<?php

use App\Enums\ProductType;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['email' => 'sourovcodes@gmail.com']);
    $this->actingAs($this->admin);
});

describe('List Products Page', function () {
    it('can render the index page', function () {
        $this->get(ProductResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can list products', function () {
        $products = Product::factory()->count(3)->create();

        Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords($products);
    });

    it('can search products by name', function () {
        $productToFind = Product::factory()->create(['name' => 'Unique Plugin']);
        $otherProduct = Product::factory()->create(['name' => 'Other Theme']);

        Livewire::test(ListProducts::class)
            ->searchTable('Unique Plugin')
            ->assertCanSeeTableRecords([$productToFind])
            ->assertCanNotSeeTableRecords([$otherProduct]);
    });

    it('can filter products by type', function () {
        $plugin = Product::factory()->plugin()->create();
        $theme = Product::factory()->theme()->create();

        Livewire::test(ListProducts::class)
            ->filterTable('type', ProductType::Plugin->value)
            ->assertCanSeeTableRecords([$plugin])
            ->assertCanNotSeeTableRecords([$theme]);
    });

    it('can filter products by active status', function () {
        $activeProduct = Product::factory()->create(['is_active' => true]);
        $inactiveProduct = Product::factory()->inactive()->create();

        Livewire::test(ListProducts::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$activeProduct])
            ->assertCanNotSeeTableRecords([$inactiveProduct]);
    });

    it('can sort products by name', function () {
        $productA = Product::factory()->create(['name' => 'Alpha Product']);
        $productZ = Product::factory()->create(['name' => 'Zeta Product']);

        Livewire::test(ListProducts::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords([$productA, $productZ], inOrder: true);
    });
});

describe('Create Product Page', function () {
    it('can render the create page', function () {
        $this->get(ProductResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can create a product', function () {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'New Plugin',
                'slug' => 'new-plugin',
                'description' => 'A new plugin description',
                'type' => ProductType::Plugin->value,
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Product::class, [
            'name' => 'New Plugin',
            'slug' => 'new-plugin',
            'type' => ProductType::Plugin->value,
            'is_active' => true,
        ]);
    });

    it('validates required fields', function () {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => '',
                'slug' => '',
                'type' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['name', 'slug', 'type']);
    });

    it('validates unique slug', function () {
        Product::factory()->create(['slug' => 'existing-slug']);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Test Product',
                'slug' => 'existing-slug',
                'type' => ProductType::Plugin->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    });

    it('can create products of different types', function (ProductType $type) {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => "Test {$type->getLabel()}",
                'slug' => "test-{$type->value}",
                'type' => $type->value,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Product::class, [
            'slug' => "test-{$type->value}",
            'type' => $type->value,
        ]);
    })->with([
        'plugin' => ProductType::Plugin,
        'theme' => ProductType::Theme,
        'source_code' => ProductType::SourceCode,
    ]);
});

describe('View Product Page', function () {
    it('can render the view page', function () {
        $product = Product::factory()->create();

        $this->get(ProductResource::getUrl('view', ['record' => $product]))
            ->assertSuccessful();
    });

    it('displays product information correctly', function () {
        $product = Product::factory()->create([
            'name' => 'View Test Product',
            'description' => 'This is a test description',
        ]);

        Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()])
            ->assertSee('View Test Product');
    });

    it('shows packages in relation manager', function () {
        $product = Product::factory()->create();
        Package::factory()->count(3)->create(['product_id' => $product->id]);

        Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful();
    });
});

describe('Edit Product Page', function () {
    it('can render the edit page', function () {
        $product = Product::factory()->create();

        $this->get(ProductResource::getUrl('edit', ['record' => $product]))
            ->assertSuccessful();
    });

    it('can retrieve product data', function () {
        $product = Product::factory()->create([
            'name' => 'Original Product',
            'slug' => 'original-product',
            'type' => ProductType::Plugin,
            'is_active' => true,
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet([
                'name' => 'Original Product',
                'slug' => 'original-product',
                'type' => ProductType::Plugin, // Filament v4 returns enum instance
                'is_active' => true,
            ]);
    });

    it('can update a product', function () {
        $product = Product::factory()->create();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'name' => 'Updated Product Name',
                'description' => 'Updated description',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();

        expect($product->name)->toBe('Updated Product Name')
            ->and($product->description)->toBe('Updated description');
    });

    it('can toggle product active status', function () {
        $product = Product::factory()->create(['is_active' => true]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        expect($product->is_active)->toBeFalse();
    });

    it('can delete a product', function () {
        $product = Product::factory()->create();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($product);
    });
});

describe('Product Global Search', function () {
    it('can find products via global search', function () {
        $product = Product::factory()->create(['name' => 'Searchable Plugin']);

        $results = ProductResource::getGlobalSearchResults('Searchable');

        expect($results->count())->toBeGreaterThan(0);
    });
});
