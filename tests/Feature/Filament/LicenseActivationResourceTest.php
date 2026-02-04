<?php

use App\Filament\Resources\LicenseActivations\LicenseActivationResource;
use App\Filament\Resources\LicenseActivations\Pages\CreateLicenseActivation;
use App\Filament\Resources\LicenseActivations\Pages\EditLicenseActivation;
use App\Filament\Resources\LicenseActivations\Pages\ListLicenseActivations;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['email' => 'sourovcodes@gmail.com']);
    $this->actingAs($this->admin);
});

describe('List License Activations Page', function () {
    it('can render the index page', function () {
        $this->get(LicenseActivationResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can list license activations', function () {
        $activations = LicenseActivation::factory()->count(3)->create();

        Livewire::test(ListLicenseActivations::class)
            ->assertCanSeeTableRecords($activations);
    });

    it('can search activations by domain', function () {
        $activationToFind = LicenseActivation::factory()->create(['domain' => 'findme.example.com']);
        $otherActivation = LicenseActivation::factory()->create(['domain' => 'other.example.com']);

        Livewire::test(ListLicenseActivations::class)
            ->searchTable('findme.example.com')
            ->assertCanSeeTableRecords([$activationToFind])
            ->assertCanNotSeeTableRecords([$otherActivation]);
    });

    it('can filter activations by license', function () {
        $license = License::factory()->create();
        $activationWithLicense = LicenseActivation::factory()->create(['license_id' => $license->id]);
        $otherActivation = LicenseActivation::factory()->create();

        Livewire::test(ListLicenseActivations::class)
            ->filterTable('license', $license->id)
            ->assertCanSeeTableRecords([$activationWithLicense])
            ->assertCanNotSeeTableRecords([$otherActivation]);
    });
});

describe('Create License Activation Page', function () {
    it('can render the create page', function () {
        $this->get(LicenseActivationResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can create a license activation', function () {
        $license = License::factory()->create();

        Livewire::test(CreateLicenseActivation::class)
            ->fillForm([
                'license_id' => $license->id,
                'domain' => 'newdomain.example.com',
                'ip_address' => '192.168.1.100',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(LicenseActivation::class, [
            'license_id' => $license->id,
            'domain' => 'newdomain.example.com',
            'ip_address' => '192.168.1.100',
        ]);
    });

    it('validates required fields', function () {
        Livewire::test(CreateLicenseActivation::class)
            ->fillForm([
                'license_id' => null,
                'domain' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['license_id', 'domain']);
    });

    it('sets activated_at automatically', function () {
        $license = License::factory()->create();

        Livewire::test(CreateLicenseActivation::class)
            ->fillForm([
                'license_id' => $license->id,
                'domain' => 'auto-activated.example.com',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $activation = LicenseActivation::where('domain', 'auto-activated.example.com')->first();
        expect($activation->activated_at)->not->toBeNull();
    });
});

describe('Edit License Activation Page', function () {
    it('can render the edit page', function () {
        $activation = LicenseActivation::factory()->create();

        $this->get(LicenseActivationResource::getUrl('edit', ['record' => $activation]))
            ->assertSuccessful();
    });

    it('can retrieve activation data', function () {
        $activation = LicenseActivation::factory()->create([
            'domain' => 'original.example.com',
            'ip_address' => '10.0.0.1',
        ]);

        Livewire::test(EditLicenseActivation::class, ['record' => $activation->getRouteKey()])
            ->assertFormSet([
                'domain' => 'original.example.com',
                'ip_address' => '10.0.0.1',
            ]);
    });

    it('can update a license activation', function () {
        $activation = LicenseActivation::factory()->create();

        Livewire::test(EditLicenseActivation::class, ['record' => $activation->getRouteKey()])
            ->fillForm([
                'domain' => 'updated.example.com',
                'ip_address' => '172.16.0.1',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $activation->refresh();

        expect($activation->domain)->toBe('updated.example.com')
            ->and($activation->ip_address)->toBe('172.16.0.1');
    });

    it('can delete a license activation', function () {
        $activation = LicenseActivation::factory()->create();

        Livewire::test(EditLicenseActivation::class, ['record' => $activation->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($activation);
    });
});

describe('License Activation Relationship', function () {
    it('activation belongs to a license', function () {
        $license = License::factory()->create();
        $activation = LicenseActivation::factory()->create(['license_id' => $license->id]);

        expect($activation->license->id)->toBe($license->id);
    });

    it('can display license information in activation list', function () {
        $license = License::factory()->create(['license_key' => 'DISPLAY-TEST-KEY']);
        LicenseActivation::factory()->create(['license_id' => $license->id]);

        Livewire::test(ListLicenseActivations::class)
            ->assertSuccessful();
    });
});
