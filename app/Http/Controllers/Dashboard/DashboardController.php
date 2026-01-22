<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\LicenseResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserResource;
use App\Models\License;
use App\Models\LicenseActivation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Cashier;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get subscriptions
        $subscriptions = $user->subscriptions()->get();

        // Get licenses
        $licenses = License::query()
            ->where('user_id', $user->id)
            ->with(['product', 'package'])
            ->withCount(['activations', 'activeActivations'])
            ->latest()
            ->get();

        // Calculate credit balance
        // In Stripe, negative balance = customer has credit, positive = customer owes money
        $creditBalance = '$0.00';
        if ($user->hasStripeId()) {
            $rawBalance = $user->rawBalance(); // In cents, negative = credit
            if ($rawBalance < 0) {
                // Customer has credit - show as positive
                $creditBalance = Cashier::formatAmount(abs($rawBalance));
            }
        }

        // Calculate stats
        $stats = [
            'total_subscriptions' => $user->subscriptions()->count(),
            'active_subscriptions' => $user->subscriptions()->where('stripe_status', 'active')->count(),
            'total_licenses' => License::where('user_id', $user->id)->count(),
            'active_licenses' => License::where('user_id', $user->id)->where('status', 'active')->count(),
            'total_activations' => LicenseActivation::query()
                ->whereHas('license', fn ($q) => $q->where('user_id', $user->id))
                ->whereNull('deactivated_at')
                ->count(),
            'credit_balance' => $creditBalance,
        ];

        return Inertia::render('dashboard/index', [
            'user' => UserResource::make($user)->resolve(),
            'stats' => $stats,
            'recent_licenses' => LicenseResource::collection($licenses->take(5))->resolve(),
            'subscriptions' => SubscriptionResource::collection($subscriptions)->resolve(),
        ]);
    }
}
