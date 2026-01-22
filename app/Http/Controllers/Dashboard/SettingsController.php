<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('dashboard/settings', [
            'user' => UserResource::make($request->user())->resolve(),
            'notifications_enabled' => true, // This could be stored in user settings
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notifications_enabled' => ['sometimes', 'boolean'],
        ]);

        // Here you would save the settings to the user or a settings table
        // For now, we'll just flash a success message

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Settings saved',
            'description' => 'Your preferences have been updated.',
        ]);

        return back();
    }
}
