<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\InvoiceController;
use App\Http\Controllers\Dashboard\LicenseController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\Dashboard\SubscriptionController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('home');
})->name('home');

Route::get('/privacy', function () {
    return Inertia::render('privacy');
})->name('privacy');

Route::get('/terms', function () {
    return Inertia::render('terms');
})->name('terms');

// Product routes
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/packages/{package}/subscribe', [ProductController::class, 'subscribe'])->name('packages.subscribe')->middleware('auth');
Route::post('/packages/{package}/swap', [ProductController::class, 'swap'])->name('packages.swap')->middleware('auth');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    // Password reset routes
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Email verification routes
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');

    // Dashboard routes
    Route::prefix('dashboard')->name('dashboard.')->middleware('verified')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');

        // Subscriptions
        Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::post('/subscriptions/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('subscriptions.resume');

        // Licenses
        Route::get('/licenses', [LicenseController::class, 'index'])->name('licenses.index');
        Route::get('/licenses/{license}', [LicenseController::class, 'show'])->name('licenses.show');
        Route::post('/licenses/{license}/deactivate-all', [LicenseController::class, 'deactivateAll'])->name('licenses.deactivate-all');
        Route::delete('/licenses/{license}/activations/{activation}', [LicenseController::class, 'deactivateActivation'])->name('licenses.activations.destroy');

        // Invoices
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');

        // Profile
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        // Settings
        Route::get('/settings', [SettingsController::class, 'show'])->name('settings.show');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});

Route::get('/flash-test/{type}', function (string $type) {
    $messages = [
        'success' => ['message' => 'Operation completed successfully!', 'description' => 'Your changes have been saved.'],
        'error' => ['message' => 'Something went wrong!', 'description' => 'Please try again or contact support.'],
        'warning' => ['message' => 'Please proceed with caution.'], // description is optional
        'info' => ['message' => 'Here is some useful information.'], // description is optional
    ];

    $toast = $messages[$type] ?? ['message' => 'Test message'];

    Inertia::flash('toast', [
        'type' => $type,
        'message' => $toast['message'],
        'description' => $toast['description'] ?? null,
    ]);

    return back();
})->whereIn('type', ['success', 'error', 'warning', 'info'])->name('flash.test');
