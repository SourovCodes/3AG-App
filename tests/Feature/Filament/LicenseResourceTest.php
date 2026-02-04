<?php

use App\Enums\LicenseStatus;
use App\Filament\Resources\Licenses\LicenseResource;
use App\Filament\Resources\Licenses\Pages\CreateLicense;
use App\Filament\Resources\Licenses\Pages\EditLicense;
use App\Filament\Resources\Licenses\Pages\ListLicenses;
use App\Filament\Resources\Licenses\Pages\ViewLicense;
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

describe('List Licenses Page', function () {
    it('can render the index page', function () {
        $this->get(LicenseResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can list licenses', function () {
        $licenses = License::factory()->count(3)->create();

        Livewire::test(ListLicenses::class)
            ->assertCanSeeTableRecords($licenses);
    });

    it('can search licenses by license key', function () {
        $license = License::factory()->create(['license_key' => 'UNIQUE-KEY-1234-5678']);
        $otherLicense = License::factory()->create(['license_key' => 'OTHER-KEY-ABCD-EFGH']);

        Livewire::test(ListLicenses::class)
            ->searchTable('UNIQUE-KEY')
            ->assertCanSeeTableRecords([$license])
            ->assertCanNotSeeTableRecords([$otherLicense]);
    });

    it('can filter licenses by status', function () {
        $activeLicense = License::factory()->active()->create();
        $suspendedLicense = License::factory()->suspended()->create();

        Livewire::test(ListLicenses::class)
            ->filterTable('status', LicenseStatus::Active->value)
            ->assertCanSeeTableRecords([$activeLicense])
            ->assertCanNotSeeTableRecords([$suspendedLicense]);
    });

    it('can filter licenses by product', function () {
        $product = Product::factory()->create();
        $licenseWithProduct = License::factory()->create(['product_id' => $product->id]);
        $otherLicense = License::factory()->create();

        Livewire::test(ListLicenses::class)
            ->filterTable('product', $product->id)
            ->assertCanSeeTableRecords([$licenseWithProduct])
            ->assertCanNotSeeTableRecords([$otherLicense]);
    });

    it('can sort licenses by created date', function () {
        $oldLicense = License::factory()->create(['created_at' => now()->subDays(10)]);
        $newLicense = License::factory()->create(['created_at' => now()]);

        Livewire::test(ListLicenses::class)
            ->sortTable('created_at', 'desc')
            ->assertCanSeeTableRecords([$newLicense, $oldLicense], inOrder: true);
    });
});

describe('Create License Page', function () {
    it('can render the create page', function () {
        $this->get(LicenseResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can create a license', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $package = Package::factory()->create(['product_id' => $product->id]);

        Livewire::test(CreateLicense::class)
            ->fillForm([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'package_id' => $package->id,
                'domain_limit' => 5,
                'status' => LicenseStatus::Active->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(License::class, [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'package_id' => $package->id,
            'domain_limit' => 5,
            'status' => LicenseStatus::Active->value,
        ]);
    });

    it('validates required fields', function () {
        Livewire::test(CreateLicense::class)
            ->fillForm([
                'user_id' => null,
                'product_id' => null,
                'package_id' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['user_id', 'product_id', 'package_id']);
    });

    it('generates a unique license key automatically', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $package = Package::factory()->create(['product_id' => $product->id]);

        Livewire::test(CreateLicense::class)
            ->fillForm([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'package_id' => $package->id,
                'status' => LicenseStatus::Active->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $license = License::where('user_id', $user->id)->first();
        expect($license->license_key)->not->toBeNull()
            ->and(strlen($license->license_key))->toBeGreaterThan(10);
    });
});

describe('View License Page', function () {
    it('can render the view page', function () {
        $license = License::factory()->create();

        $this->get(LicenseResource::getUrl('view', ['record' => $license]))
            ->assertSuccessful();
    });

    it('displays license information correctly', function () {
        $license = License::factory()->create([
            'license_key' => 'VIEW-TEST-KEY-1234',
            'domain_limit' => 10,
        ]);

        Livewire::test(ViewLicense::class, ['record' => $license->getRouteKey()])
            ->assertSee('VIEW-TEST-KEY-1234');
    });

    it('shows license activations in relation manager', function () {
        $license = License::factory()->create();
        LicenseActivation::factory()->count(2)->create(['license_id' => $license->id]);

        Livewire::test(ViewLicense::class, ['record' => $license->getRouteKey()])
            ->assertSuccessful();
    });
});

describe('Edit License Page', function () {
    it('can render the edit page', function () {
        $license = License::factory()->create();

        $this->get(LicenseResource::getUrl('edit', ['record' => $license]))
            ->assertSuccessful();
    });

    it('can retrieve license data', function () {
        $license = License::factory()->create([
            'domain_limit' => 15,
            'status' => LicenseStatus::Active,
        ]);

        Livewire::test(EditLicense::class, ['record' => $license->getRouteKey()])
            ->assertFormSet([
                'domain_limit' => 15,
                'status' => LicenseStatus::Active,
            ]);
    });

    it('can update a license', function () {
        $license = License::factory()->create(['domain_limit' => 5]);

        Livewire::test(EditLicense::class, ['record' => $license->getRouteKey()])
            ->fillForm([
                'domain_limit' => 20,
                'status' => LicenseStatus::Suspended->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $license->refresh();

        expect($license->domain_limit)->toBe(20)
            ->and($license->status)->toBe(LicenseStatus::Suspended);
    });

    it('can suspend a license', function () {
        $license = License::factory()->active()->create();

        Livewire::test(EditLicense::class, ['record' => $license->getRouteKey()])
            ->fillForm([
                'status' => LicenseStatus::Suspended->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $license->refresh();
        expect($license->status)->toBe(LicenseStatus::Suspended);
    });

    it('can delete a license', function () {
        $license = License::factory()->create();

        Livewire::test(EditLicense::class, ['record' => $license->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($license);
    });
});

describe('License Bulk Actions', function () {
    it('can bulk suspend licenses', function () {
        $licenses = License::factory()->count(3)->active()->create();

        Livewire::test(ListLicenses::class)
            ->callTableBulkAction('suspend', $licenses);

        foreach ($licenses as $license) {
            $license->refresh();
            expect($license->status)->toBe(LicenseStatus::Suspended);
        }
    });

    it('can bulk activate licenses', function () {
        $licenses = License::factory()->count(3)->suspended()->create();

        Livewire::test(ListLicenses::class)
            ->callTableBulkAction('activate', $licenses);

        foreach ($licenses as $license) {
            $license->refresh();
            expect($license->status)->toBe(LicenseStatus::Active);
        }
    });
});
