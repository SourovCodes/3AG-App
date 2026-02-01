<?php

use App\Jobs\UploadNaldaCsvToSftp;
use App\Models\NaldaCsvUpload;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Queue;

it('can be serialized with encrypted password', function () {
    $csvUpload = NaldaCsvUpload::factory()->create();
    $encryptedPassword = Crypt::encryptString('test-password');

    $job = new UploadNaldaCsvToSftp($csvUpload, $encryptedPassword);

    expect($job->csvUpload->id)->toBe($csvUpload->id)
        ->and($job->encryptedPassword)->toBe($encryptedPassword);
});

it('is dispatched to the queue', function () {
    Queue::fake();

    $csvUpload = NaldaCsvUpload::factory()->create();
    $encryptedPassword = Crypt::encryptString('test-password');

    UploadNaldaCsvToSftp::dispatch($csvUpload, $encryptedPassword);

    Queue::assertPushed(UploadNaldaCsvToSftp::class, function ($job) use ($csvUpload) {
        return $job->csvUpload->id === $csvUpload->id;
    });
});

it('marks upload as failed when csv file is not found', function () {
    $csvUpload = NaldaCsvUpload::factory()->create(['status' => 'pending']);
    $encryptedPassword = Crypt::encryptString('test-password');

    $job = new UploadNaldaCsvToSftp($csvUpload, $encryptedPassword);
    $job->handle();

    $csvUpload->refresh();

    expect($csvUpload->status)->toBe('failed')
        ->and($csvUpload->error_message)->toBe('CSV file not found.');
});
