<?php

use App\Http\Controllers\Api\V3\LicenseController;
use App\Http\Controllers\Api\V3\NaldaController;
use App\Http\Controllers\Api\V3\UpdateController;
use App\Http\Middleware\ValidateActiveLicense;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::prefix('v3')->name('api.v3.')->middleware(['throttle:api'])->group(function () {
    Route::prefix('licenses')->name('licenses.')->group(function () {
        Route::post('/validate', [LicenseController::class, 'validate'])->name('validate');
        Route::post('/activate', [LicenseController::class, 'activate'])
            ->middleware('throttle:20,1') // 20 requests per minute for activation
            ->name('activate');
        Route::post('/deactivate', [LicenseController::class, 'deactivate'])
            ->middleware('throttle:20,1') // 20 requests per minute for deactivation
            ->name('deactivate');
        Route::post('/check', [LicenseController::class, 'check'])->name('check');
    });

    Route::prefix('nalda')->name('nalda.')->middleware([ValidateActiveLicense::class])->group(function () {
        Route::post('/csv-upload', [NaldaController::class, 'uploadCsv'])
            ->middleware('throttle:10,1') // 10 uploads per minute
            ->name('csv-upload');
        Route::get('/csv-upload/list', [NaldaController::class, 'listCsvUploads'])->name('csv-upload.list');
        Route::post('/sftp-validate', [NaldaController::class, 'validateSftp'])
            ->middleware('throttle:30,1') // 30 validation attempts per minute
            ->name('sftp-validate');
    });

    Route::post('/update/check', [UpdateController::class, 'check'])
        ->middleware([ValidateActiveLicense::class])
        ->name('update.check');
});
