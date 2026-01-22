<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $subscriptions = $user->subscriptions()->latest()->get();

        // Get Stripe billing portal URL
        $billingPortalUrl = null;
        if ($user->hasStripeId()) {
            try {
                $billingPortalUrl = $user->billingPortalUrl(route('dashboard.subscriptions.index'));
            } catch (\Exception) {
                // Stripe not configured or other error
            }
        }

        return Inertia::render('dashboard/subscriptions/index', [
            'subscriptions' => SubscriptionResource::collection($subscriptions)->resolve(),
            'billing_portal_url' => $billingPortalUrl,
        ]);
    }

    public function cancel(Request $request, int $subscriptionId): RedirectResponse
    {
        $user = $request->user();

        $subscription = $user->subscriptions()->findOrFail($subscriptionId);

        try {
            $subscription->cancel();

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Subscription cancelled',
                'description' => 'Your subscription will remain active until the end of the billing period.',
            ]);
        } catch (\Exception $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Failed to cancel subscription',
                'description' => $e->getMessage(),
            ]);
        }

        return back();
    }

    public function resume(Request $request, int $subscriptionId): RedirectResponse
    {
        $user = $request->user();

        $subscription = $user->subscriptions()->findOrFail($subscriptionId);

        try {
            $subscription->resume();

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Subscription resumed',
                'description' => 'Your subscription has been reactivated.',
            ]);
        } catch (\Exception $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Failed to resume subscription',
                'description' => $e->getMessage(),
            ]);
        }

        return back();
    }
}
