<?php

use App\Enums\LicenseStatus;
use App\Enums\NaldaCsvType;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\NaldaCsvUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

describe('License Validation Middleware', function () {
    it('returns error when license_key is missing', function () {
        $response = $this->postJson('/api/v3/nalda/csv-upload', [
            'domain' => 'example.com',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'License key and domain are required.');
    });

    it('returns error when domain is missing', function () {
        $response = $this->postJson('/api/v3/nalda/csv-upload', [
            'license_key' => 'TEST-1234-5678-ABCD',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'License key and domain are required.');
    });

    it('returns error for invalid license key', function () {
        $response = $this->postJson('/api/v3/nalda/csv-upload', [
            'license_key' => 'INVALID-KEY',
            'domain' => 'example.com',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Invalid license key.');
    });

    it('returns error for inactive license', function () {
        $license = License::factory()->suspended()->create();

        $response = $this->postJson('/api/v3/nalda/csv-upload', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'License is not active.');
    });

    it('returns error for expired license', function () {
        $license = License::factory()->create([
            'status' => LicenseStatus::Active,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->postJson('/api/v3/nalda/csv-upload', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'License has expired.');
    });

    it('returns error when domain is not activated', function () {
        $license = License::factory()->active()->create([
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->postJson('/api/v3/nalda/csv-upload', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'License is not activated on this domain.');
    });

    it('returns error when domain activation is deactivated', function () {
        $license = License::factory()->active()->create([
            'expires_at' => now()->addYear(),
        ]);

        LicenseActivation::factory()->deactivated()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/nalda/csv-upload', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'License is not activated on this domain.');
    });
});

describe('CSV Upload Endpoint', function () {
    it('validates required fields', function () {
        $license = License::factory()->active()->create(['expires_at' => now()->addYear()]);
        LicenseActivation::factory()->active()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/nalda/csv-upload', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['csv_type', 'sftp_host', 'sftp_username', 'sftp_password', 'csv_file']);
    });

    it('validates sftp_host must be nalda.com domain', function () {
        $license = License::factory()->active()->create(['expires_at' => now()->addYear()]);
        LicenseActivation::factory()->active()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/nalda/csv-upload', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'csv_type' => 'orders',
            'sftp_host' => 'sftp.evil.com',
            'sftp_username' => 'user',
            'sftp_password' => 'pass',
            'csv_file' => UploadedFile::fake()->create('test.csv', 100),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sftp_host']);
    });

    it('validates csv_type must be orders or products', function () {
        $license = License::factory()->active()->create(['expires_at' => now()->addYear()]);
        LicenseActivation::factory()->active()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/nalda/csv-upload', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'csv_type' => 'invalid',
            'sftp_host' => 'sftp.nalda.com',
            'sftp_username' => 'user',
            'sftp_password' => 'pass',
            'csv_file' => UploadedFile::fake()->create('test.csv', 100),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['csv_type']);
    });

    it('validates csv file max size is 10MB', function () {
        $license = License::factory()->active()->create(['expires_at' => now()->addYear()]);
        LicenseActivation::factory()->active()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/nalda/csv-upload', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'csv_type' => 'orders',
            'sftp_host' => 'sftp.nalda.com',
            'sftp_username' => 'user',
            'sftp_password' => 'pass',
            'csv_file' => UploadedFile::fake()->create('test.csv', 11000),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['csv_file']);
    });

    it('normalizes domain with www prefix', function () {
        $license = License::factory()->active()->create(['expires_at' => now()->addYear()]);
        LicenseActivation::factory()->active()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/nalda/csv-upload', [
            'license_key' => $license->license_key,
            'domain' => 'www.example.com',
        ]);

        $response->assertStatus(422);
    });

    it('normalizes domain with protocol', function () {
        $license = License::factory()->active()->create(['expires_at' => now()->addYear()]);
        LicenseActivation::factory()->active()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/nalda/csv-upload', [
            'license_key' => $license->license_key,
            'domain' => 'https://example.com',
        ]);

        $response->assertStatus(422);
    });
});

describe('CSV Upload List Endpoint', function () {
    it('returns paginated list of uploads for license and domain', function () {
        $license = License::factory()->active()->create(['expires_at' => now()->addYear()]);
        LicenseActivation::factory()->active()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        NaldaCsvUpload::factory()->count(3)->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        NaldaCsvUpload::factory()->count(2)->create([
            'license_id' => $license->id,
            'domain' => 'other.com',
        ]);

        $response = $this->getJson('/api/v3/nalda/csv-upload/list?'.http_build_query([
            'license_key' => $license->license_key,
            'domain' => 'example.com',
        ]));

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'file_name',
                        'file_size',
                        'sftp_path',
                        'status',
                        'uploaded_at',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    });

    it('respects per_page parameter', function () {
        $license = License::factory()->active()->create(['expires_at' => now()->addYear()]);
        LicenseActivation::factory()->active()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        NaldaCsvUpload::factory()->count(10)->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->getJson('/api/v3/nalda/csv-upload/list?'.http_build_query([
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'per_page' => 5,
        ]));

        $response->assertSuccessful()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 10);
    });

    it('returns empty list when no uploads exist', function () {
        $license = License::factory()->active()->create(['expires_at' => now()->addYear()]);
        LicenseActivation::factory()->active()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->getJson('/api/v3/nalda/csv-upload/list?'.http_build_query([
            'license_key' => $license->license_key,
            'domain' => 'example.com',
        ]));

        $response->assertSuccessful()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    });
});

describe('SFTP Validate Endpoint', function () {
    it('validates required fields', function () {
        $license = License::factory()->active()->create(['expires_at' => now()->addYear()]);
        LicenseActivation::factory()->active()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/nalda/sftp-validate', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sftp_host', 'sftp_username', 'sftp_password']);
    });

    it('validates sftp_host must be nalda.com domain', function () {
        $license = License::factory()->active()->create(['expires_at' => now()->addYear()]);
        LicenseActivation::factory()->active()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/nalda/sftp-validate', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'sftp_host' => 'sftp.evil.com',
            'sftp_username' => 'user',
            'sftp_password' => 'pass',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sftp_host']);
    });

    it('validates sftp_port range', function () {
        $license = License::factory()->active()->create(['expires_at' => now()->addYear()]);
        LicenseActivation::factory()->active()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
        ]);

        $response = $this->postJson('/api/v3/nalda/sftp-validate', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'sftp_host' => 'sftp.nalda.com',
            'sftp_port' => 70000,
            'sftp_username' => 'user',
            'sftp_password' => 'pass',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sftp_port']);
    });
});

describe('NaldaCsvType Enum', function () {
    it('returns correct SFTP folder for orders', function () {
        expect(NaldaCsvType::Orders->getSftpFolder())->toBe('/order-status');
    });

    it('returns correct SFTP folder for products', function () {
        expect(NaldaCsvType::Products->getSftpFolder())->toBe('/');
    });
});
