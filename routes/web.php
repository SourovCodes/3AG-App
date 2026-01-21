<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('/flash-test/{type}', function (string $type) {
    $messages = [
        'success' => 'Operation completed successfully!',
        'error' => 'Something went wrong!',
        'warning' => 'Please proceed with caution.',
        'info' => 'Here is some useful information.',
    ];

    Inertia::flash('toast', [
        'type' => $type,
        'message' => $messages[$type] ?? 'Test message',
    ]);

    return back();
})->whereIn('type', ['success', 'error', 'warning', 'info'])->name('flash.test');
