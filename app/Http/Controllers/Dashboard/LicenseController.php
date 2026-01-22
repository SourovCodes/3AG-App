<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\LicenseDetailResource;
use App\Http\Resources\LicenseResource;
use App\Models\License;
use App\Models\LicenseActivation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LicenseController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $licenses = License::query()
            ->where('user_id', $user->id)
            ->with(['product', 'package'])
            ->withCount(['activations', 'activeActivations'])
            ->latest()
            ->get();

        return Inertia::render('dashboard/licenses/index', [
            'licenses' => LicenseResource::collection($licenses)->resolve(),
        ]);
    }

    public function show(Request $request, License $license): Response
    {
        $user = $request->user();

        // Ensure the license belongs to the user
        if ($license->user_id !== $user->id) {
            abort(403);
        }

        $license->load(['product', 'package', 'activations']);

        return Inertia::render('dashboard/licenses/show', [
            'license' => LicenseDetailResource::make($license)->resolve(),
        ]);
    }

    public function deactivateAll(Request $request, License $license): RedirectResponse
    {
        $user = $request->user();

        if ($license->user_id !== $user->id) {
            abort(403);
        }

        $count = $license->activeActivations()->count();

        $license->activeActivations()->update([
            'deactivated_at' => now(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Domains deactivated',
            'description' => "{$count} domain(s) have been deactivated from this license.",
        ]);

        return back();
    }

    public function deactivateActivation(Request $request, License $license, LicenseActivation $activation): RedirectResponse
    {
        $user = $request->user();

        if ($license->user_id !== $user->id) {
            abort(403);
        }

        if ($activation->license_id !== $license->id) {
            abort(404);
        }

        $domain = $activation->domain;

        $activation->update([
            'deactivated_at' => now(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Domain deactivated',
            'description' => "The license has been deactivated from {$domain}.",
        ]);

        return back();
    }
}
