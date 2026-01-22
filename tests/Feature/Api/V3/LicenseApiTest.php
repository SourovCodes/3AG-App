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
                'data' => [
                    'license_key' => $this->license->license_key,
                    'status' => 'active',
                    'is_active' => true,
                    'product' => [
                        'name' => $this->product->name,
                        'slug' => $this->product->slug,
                    ],
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

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'license_not_found',
                ],
            ]);
    });

    it('requires a license key and product_slug', function () {
        $response = $this->postJson('/api/v3/licenses/validate', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['license_key', 'product_slug']);
    });

    it('updates last_validated_at timestamp', function () {
        $this->license->update(['last_validated_at' => null]);
        $this->assertNull($this->license->fresh()->last_validated_at);

        $this->postJson('/api/v3/licenses/validate', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
        ]);

        $this->assertNotNull($this->license->fresh()->last_validated_at);
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
                'data' => [
                    'activation' => [
                        'domain' => 'example.com',
                        'is_active' => true,
                    ],
                    'license' => [
                        'license_key' => $this->license->license_key,
                    ],
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

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'license_not_found',
                ],
            ]);
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

    it('returns existing activation if already activated on domain', function () {
        $activation = LicenseActivation::factory()->active()->create([
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
                'data' => [
                    'activation' => [
                        'id' => $activation->id,
                        'domain' => 'example.com',
                    ],
                ],
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
        // Create activations up to the limit
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

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'license_inactive',
                ],
            ]);
    });

    it('returns 404 for invalid license key', function () {
        $response = $this->postJson('/api/v3/licenses/activate', [
            'license_key' => 'INVALID-KEY-1234-5678',
            'product_slug' => $this->product->slug,
            'domain' => 'example.com',
        ]);

        $response->assertNotFound();
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
                'data' => [
                    'activation' => [
                        'id' => $activation->id,
                        'is_active' => false,
                    ],
                ],
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

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'license_not_found',
                ],
            ]);
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

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'activation_not_found',
                ],
            ]);
    });

    it('returns 404 for invalid license key', function () {
        $response = $this->postJson('/api/v3/licenses/deactivate', [
            'license_key' => 'INVALID-KEY-1234-5678',
            'product_slug' => $this->product->slug,
            'domain' => 'example.com',
        ]);

        $response->assertNotFound();
    });
});

describe('POST /api/v3/licenses/check', function () {
    it('returns active status for activated domain', function () {
        $activation = LicenseActivation::factory()->active()->create([
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
                'data' => [
                    'is_active' => true,
                    'license_valid' => true,
                    'activation' => [
                        'id' => $activation->id,
                        'domain' => 'example.com',
                    ],
                ],
            ]);
    });

    it('returns 404 for valid license key but wrong product', function () {
        $otherProduct = Product::factory()->create(['slug' => 'other-plugin']);

        LicenseActivation::factory()->active()->create([
            'license_id' => $this->license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/licenses/check', [
            'license_key' => $this->license->license_key,
            'product_slug' => $otherProduct->slug,
            'domain' => 'example.com',
        ]);

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'license_not_found',
                ],
            ]);
    });

    it('returns inactive status for non-activated domain', function () {
        $response = $this->postJson('/api/v3/licenses/check', [
            'license_key' => $this->license->license_key,
            'product_slug' => $this->product->slug,
            'domain' => 'notactivated.com',
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_active' => false,
                    'license_valid' => true,
                    'message' => 'License is not activated on this domain.',
                ],
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
                'data' => [
                    'is_active' => false,
                    'license_valid' => false,
                ],
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

    it('returns 404 for invalid license key', function () {
        $response = $this->postJson('/api/v3/licenses/check', [
            'license_key' => 'INVALID-KEY-1234-5678',
            'product_slug' => $this->product->slug,
            'domain' => 'example.com',
        ]);

        $response->assertNotFound();
    });
});

describe('Cross-product license security', function () {
    it('prevents using license from product A in product B across all endpoints', function () {
        $productA = Product::factory()->create(['slug' => 'plugin-a']);
        $productB = Product::factory()->create(['slug' => 'plugin-b']);

        $licenseForA = License::factory()->active()->create([
            'product_id' => $productA->id,
            'package_id' => Package::factory()->create(['product_id' => $productA->id])->id,
        ]);

        // Try to validate license for product A using product B slug
        $this->postJson('/api/v3/licenses/validate', [
            'license_key' => $licenseForA->license_key,
            'product_slug' => $productB->slug,
        ])->assertNotFound();

        // Try to activate license for product A on product B
        $this->postJson('/api/v3/licenses/activate', [
            'license_key' => $licenseForA->license_key,
            'product_slug' => $productB->slug,
            'domain' => 'example.com',
        ])->assertNotFound();

        // Ensure activation works with correct product
        $this->postJson('/api/v3/licenses/activate', [
            'license_key' => $licenseForA->license_key,
            'product_slug' => $productA->slug,
            'domain' => 'example.com',
        ])->assertCreated();

        // Try to deactivate using wrong product
        $this->postJson('/api/v3/licenses/deactivate', [
            'license_key' => $licenseForA->license_key,
            'product_slug' => $productB->slug,
            'domain' => 'example.com',
        ])->assertNotFound();

        // Try to check using wrong product
        $this->postJson('/api/v3/licenses/check', [
            'license_key' => $licenseForA->license_key,
            'product_slug' => $productB->slug,
            'domain' => 'example.com',
        ])->assertNotFound();
    });
});

describe('API v3 validation', function () {
    it('returns JSON error for invalid requests', function () {
        $response = $this->postJson('/api/v3/licenses/validate', [
            'license_key' => '',
            'product_slug' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => ['license_key', 'product_slug'],
            ]);
    });

    it('handles malformed JSON gracefully', function () {
        $response = $this->post('/api/v3/licenses/validate', [], [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        $response->assertUnprocessable();
    });
});
