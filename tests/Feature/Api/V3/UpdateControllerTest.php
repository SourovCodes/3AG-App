<?php

use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\Product;

describe('Update Check API', function () {
    it('returns product version and download url for valid license', function () {
        $product = Product::factory()->create([
            'slug' => 'test-product',
            'version' => '2.1.0',
            'download_url' => 'https://example.com/download/test-product-2.1.0.zip',
            'is_active' => true,
        ]);

        $license = License::factory()->active()->create([
            'expires_at' => now()->addYear(),
        ]);

        LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/update/check', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => 'test-product',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.version', '2.1.0')
            ->assertJsonPath('data.download_url', 'https://example.com/download/test-product-2.1.0.zip');
    });

    it('returns 404 when product does not exist', function () {
        $license = License::factory()->active()->create([
            'expires_at' => now()->addYear(),
        ]);

        LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/update/check', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => 'non-existent-product',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('message', 'Product not found.');
    });

    it('returns 404 when product is inactive', function () {
        Product::factory()->inactive()->create([
            'slug' => 'inactive-product',
        ]);

        $license = License::factory()->active()->create([
            'expires_at' => now()->addYear(),
        ]);

        LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/update/check', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => 'inactive-product',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('message', 'Product not found.');
    });

    it('returns validation error when license_key is missing', function () {
        $response = $this->postJson('/api/v3/update/check', [
            'domain' => 'example.com',
            'product_slug' => 'test-product',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'License key and domain are required.');
    });

    it('returns validation error when domain is missing', function () {
        $response = $this->postJson('/api/v3/update/check', [
            'license_key' => 'TEST-1234-5678-ABCD',
            'product_slug' => 'test-product',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'License key and domain are required.');
    });

    it('returns error for invalid license key', function () {
        $response = $this->postJson('/api/v3/update/check', [
            'license_key' => 'INVALID-KEY',
            'domain' => 'example.com',
            'product_slug' => 'test-product',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Invalid license key.');
    });

    it('returns error when license is not activated on domain', function () {
        $license = License::factory()->active()->create([
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->postJson('/api/v3/update/check', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => 'test-product',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'License is not activated on this domain.');
    });
});
