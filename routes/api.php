<?php

use App\Http\Controllers\Api\V3\LicenseController;
use App\Http\Controllers\Api\V3\NaldaController;
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
        Route::post('/activate', [LicenseController::class, 'activate'])->name('activate');
        Route::post('/deactivate', [LicenseController::class, 'deactivate'])->name('deactivate');
        Route::post('/check', [LicenseController::class, 'check'])->name('check');
    });

    Route::prefix('nalda')->name('nalda.')->middleware([ValidateActiveLicense::class])->group(function () {
        Route::post('/csv-upload', [NaldaController::class, 'uploadCsv'])->name('csv-upload');
        Route::get('/csv-upload/list', [NaldaController::class, 'listCsvUploads'])->name('csv-upload.list');
        Route::post('/sftp-validate', [NaldaController::class, 'validateSftp'])->name('sftp-validate');
    });
});
