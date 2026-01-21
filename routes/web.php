<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('home');
})->name('home');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
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
