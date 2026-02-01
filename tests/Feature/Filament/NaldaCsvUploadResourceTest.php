<?php

use App\Filament\Resources\NaldaCsvUploads\NaldaCsvUploadResource;
use App\Filament\Resources\NaldaCsvUploads\Pages\CreateNaldaCsvUpload;
use App\Filament\Resources\NaldaCsvUploads\Pages\EditNaldaCsvUpload;
use App\Filament\Resources\NaldaCsvUploads\Pages\ListNaldaCsvUploads;
use App\Models\License;
use App\Models\NaldaCsvUpload;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['email' => 'sourovcodes@gmail.com']));
});

it('can render the index page', function () {
    $this->get(NaldaCsvUploadResource::getUrl('index'))
        ->assertSuccessful();
});

it('can list nalda csv uploads', function () {
    $uploads = NaldaCsvUpload::factory()->count(3)->create();

    Livewire::test(ListNaldaCsvUploads::class)
        ->assertCanSeeTableRecords($uploads);
});

it('can render the create page', function () {
    $this->get(NaldaCsvUploadResource::getUrl('create'))
        ->assertSuccessful();
});

it('can create a nalda csv upload', function () {
    $license = License::factory()->create();

    Livewire::test(CreateNaldaCsvUpload::class)
        ->fillForm([
            'license_id' => $license->id,
            'domain' => 'example.com',
            'csv_type' => 'orders',
            'sftp_host' => 'sftp.example.com',
            'sftp_port' => 22,
            'sftp_username' => 'testuser',
            'status' => 'pending',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(NaldaCsvUpload::class, [
        'license_id' => $license->id,
        'domain' => 'example.com',
        'csv_type' => 'orders',
        'sftp_host' => 'sftp.example.com',
        'sftp_username' => 'testuser',
    ]);
});

it('can render the edit page', function () {
    $upload = NaldaCsvUpload::factory()->create();

    $this->get(NaldaCsvUploadResource::getUrl('edit', ['record' => $upload]))
        ->assertSuccessful();
});

it('can update a nalda csv upload', function () {
    $upload = NaldaCsvUpload::factory()->create();

    Livewire::test(EditNaldaCsvUpload::class, ['record' => $upload->getRouteKey()])
        ->fillForm([
            'domain' => 'updated-domain.com',
            'sftp_host' => 'new-sftp.example.com',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $upload->refresh();

    expect($upload->domain)->toBe('updated-domain.com')
        ->and($upload->sftp_host)->toBe('new-sftp.example.com');
});

it('validates required fields on create', function () {
    Livewire::test(CreateNaldaCsvUpload::class)
        ->fillForm([
            'license_id' => null,
            'domain' => null,
            'csv_type' => null,
            'sftp_host' => null,
            'sftp_username' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'license_id' => 'required',
            'domain' => 'required',
            'csv_type' => 'required',
            'sftp_host' => 'required',
            'sftp_username' => 'required',
        ]);
});

it('can filter by status', function () {
    $pendingUpload = NaldaCsvUpload::factory()->create(['status' => 'pending']);
    $completedUpload = NaldaCsvUpload::factory()->completed()->create();

    Livewire::test(ListNaldaCsvUploads::class)
        ->assertCanSeeTableRecords([$pendingUpload, $completedUpload])
        ->filterTable('status', 'completed')
        ->assertCanSeeTableRecords([$completedUpload])
        ->assertCanNotSeeTableRecords([$pendingUpload]);
});

it('can search by domain', function () {
    $upload1 = NaldaCsvUpload::factory()->create(['domain' => 'example.com']);
    $upload2 = NaldaCsvUpload::factory()->create(['domain' => 'other.com']);

    Livewire::test(ListNaldaCsvUploads::class)
        ->searchTable('example.com')
        ->assertCanSeeTableRecords([$upload1])
        ->assertCanNotSeeTableRecords([$upload2]);
});
