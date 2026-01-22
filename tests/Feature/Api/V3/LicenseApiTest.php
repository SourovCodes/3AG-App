<?php

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->product = Product::factory()->create(['slug' => 'test-plugin', 'version' => '1.0.0']);
    $this->package = Package::factory()->create(['product_id' => $this->product->id, 'domain_limit' => 3]);
    $this->user = User::factory()->create();
    $this->license = License::factory()->active()->create([
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'package_id' => $this->package->id,
        'domain_limit' => 3,
    ]);
});

describe('POST /api/v3/licenses/validate', function () {
    it('validates a valid license key for correct product', function () {
        $response = $this->postJson('/api/v3/licenses/validate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'license' => [
                    'valid' => true,
                    'product' => $this->product->name,
                    'version' => '1.0.0',
                ],
            ]);
    });

    it('returns 404 for valid license key but wrong product', function () {
        $otherProduct = Product::factory()->create(['slug' => 'other-plugin']);

        $response = $this->postJson('/api/v3/licenses/validate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $otherProduct->slug,
        ]);

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'license_not_found',
                ],
            ]);
    });

    it('returns 404 for invalid license key', function () {
        $response = $this->postJson('/api/v3/licenses/validate', [
            'license_key' => 'INVALID-KEY-1234-5678',
            'product_slug' => $this->product->slug,
        ]);

        $response->assertNotFound();
    });

    it('requires a license key and product_slug', function () {
        $response = $this->postJson('/api/v3/licenses/validate', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['license_key', 'product_slug']);
    });

    it('updates last_validated_at timestamp', function () {
        $this->license->update(['last_validated_at' => null]);

        $this->postJson('/api/v3/licenses/validate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
        ]);

        $this->assertNotNull($this->license->fresh()->last_validated_at);
    });

    it('returns domain usage information', function () {
        LicenseActivation::factory()->active()->count(2)->create([
            'license_id' => $this->license->id,
        ]);

        $response = $this->postJson('/api/v3/licenses/validate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'license' => [
                    'domain_limit' => 3,
                    'domains_used' => 2,
                ],
            ]);
    });
});

describe('POST /api/v3/licenses/activate', function () {
    it('activates a license on a new domain', function () {
        $response = $this->postJson('/api/v3/licenses/activate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'example.com',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'License activated successfully.',
                'license' => [
                    'valid' => true,
                    'domains_used' => 1,
                ],
            ]);

        $this->assertDatabaseHas('license_activations', [
            'license_id' => $this->license->id,
            'domain' => 'example.com',
            'deactivated_at' => null,
        ]);
    });

    it('returns 404 for valid license key but wrong product', function () {
        $otherProduct = Product::factory()->create(['slug' => 'other-plugin']);

        $response = $this->postJson('/api/v3/licenses/activate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $otherProduct->slug,
            'domain' => 'example.com',
        ]);

        $response->assertNotFound();
    });

    it('normalizes domain by removing protocol and www', function () {
        $response = $this->postJson('/api/v3/licenses/activate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'https://www.example.com/path',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('license_activations', [
            'license_id' => $this->license->id,
            'domain' => 'example.com',
        ]);
    });

    it('returns success if already activated on domain', function () {
        LicenseActivation::factory()->active()->create([
            'license_id' => $this->license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/licenses/activate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'example.com',
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'License already activated on this domain.',
            ]);

        // Ensure no duplicate was created
        expect(LicenseActivation::where('license_id', $this->license->id)->where('domain', 'example.com')->count())->toBe(1);
    });

    it('reactivates a previously deactivated domain', function () {
        $activation = LicenseActivation::factory()->deactivated()->create([
            'license_id' => $this->license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/licenses/activate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'example.com',
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'License reactivated on this domain.',
            ]);

        expect($activation->fresh()->deactivated_at)->toBeNull();
    });

    it('prevents activation when domain limit is reached', function () {
        LicenseActivation::factory()->active()->count(3)->create([
            'license_id' => $this->license->id,
        ]);

        $response = $this->postJson('/api/v3/licenses/activate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'newdomain.com',
        ]);

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'domain_limit_reached',
                ],
            ]);
    });

    it('prevents activation for inactive license', function () {
        $this->license->update(['status' => LicenseStatus::Suspended]);

        $response = $this->postJson('/api/v3/licenses/activate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'example.com',
        ]);

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'license_inactive',
                ],
            ]);
    });

    it('prevents activation for expired license', function () {
        $this->license->update([
            'status' => LicenseStatus::Active,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->postJson('/api/v3/licenses/activate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'example.com',
        ]);

        $response->assertForbidden();
    });

    it('requires license_key, product_slug and domain', function () {
        $response = $this->postJson('/api/v3/licenses/activate', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['license_key', 'product_slug', 'domain']);
    });
});

describe('POST /api/v3/licenses/deactivate', function () {
    it('deactivates a license from a domain', function () {
        $activation = LicenseActivation::factory()->active()->create([
            'license_id' => $this->license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/licenses/deactivate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'example.com',
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'License deactivated successfully.',
            ]);

        expect($activation->fresh()->deactivated_at)->not->toBeNull();
    });

    it('returns 404 for valid license key but wrong product', function () {
        $otherProduct = Product::factory()->create(['slug' => 'other-plugin']);

        LicenseActivation::factory()->active()->create([
            'license_id' => $this->license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/licenses/deactivate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $otherProduct->slug,
            'domain' => 'example.com',
        ]);

        $response->assertNotFound();
    });

    it('normalizes domain before deactivation', function () {
        LicenseActivation::factory()->active()->create([
            'license_id' => $this->license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/licenses/deactivate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'https://www.example.com',
        ]);

        $response->assertSuccessful();
    });

    it('returns 404 for non-existent activation', function () {
        $response = $this->postJson('/api/v3/licenses/deactivate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'notactivated.com',
        ]);

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'activation_not_found',
                ],
            ]);
    });

    it('returns 404 for already deactivated domain', function () {
        LicenseActivation::factory()->deactivated()->create([
            'license_id' => $this->license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/licenses/deactivate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'example.com',
        ]);

        $response->assertNotFound();
    });
});

describe('POST /api/v3/licenses/check', function () {
    it('returns activated true for activated domain', function () {
        LicenseActivation::factory()->active()->create([
            'license_id' => $this->license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/licenses/check', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'example.com',
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'activated' => true,
                'license_valid' => true,
                'license' => [
                    'valid' => true,
                    'version' => '1.0.0',
                ],
            ]);
    });

    it('returns 404 for valid license key but wrong product', function () {
        $otherProduct = Product::factory()->create(['slug' => 'other-plugin']);

        $response = $this->postJson('/api/v3/licenses/check', [
            'license_key' => $this->license->license_key,
            'product_slug' => $otherProduct->slug,
            'domain' => 'example.com',
        ]);

        $response->assertNotFound();
    });

    it('returns activated false for non-activated domain', function () {
        $response = $this->postJson('/api/v3/licenses/check', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'notactivated.com',
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'activated' => false,
                'license_valid' => true,
            ]);
    });

    it('returns license_valid false for expired license', function () {
        $this->license->update([
            'status' => LicenseStatus::Active,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->postJson('/api/v3/licenses/check', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'example.com',
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'activated' => false,
                'license_valid' => false,
            ]);
    });

    it('updates last_checked_at for activation', function () {
        $activation = LicenseActivation::factory()->active()->create([
            'license_id' => $this->license->id,
            'domain' => 'example.com',
            'last_checked_at' => null,
        ]);

        $this->postJson('/api/v3/licenses/check', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'example.com',
        ]);

        expect($activation->fresh()->last_checked_at)->not->toBeNull();
    });
});

describe('Cross-product license security', function () {
    it('prevents using license from product A in product B', function () {
        $productA = Product::factory()->create(['slug' => 'plugin-a']);
        $productB = Product::factory()->create(['slug' => 'plugin-b']);

        $licenseForA = License::factory()->active()->create([
            'product_id' => $productA->id,
            'package_id' => Package::factory()->create(['product_id' => $productA->id])->id,
        ]);

        // All endpoints should return 404 when using wrong product
        $this->postJson('/api/v3/licenses/validate', [
            'license_key' => $licenseForA->license_key,
            'product_slug' => $productB->slug,
        ])->assertNotFound();

        $this->postJson('/api/v3/licenses/activate', [
            'license_key' => $licenseForA->license_key,
            'product_slug' => $productB->slug,
            'domain' => 'example.com',
        ])->assertNotFound();

        $this->postJson('/api/v3/licenses/check', [
            'license_key' => $licenseForA->license_key,
            'product_slug' => $productB->slug,
            'domain' => 'example.com',
        ])->assertNotFound();
    });
});
