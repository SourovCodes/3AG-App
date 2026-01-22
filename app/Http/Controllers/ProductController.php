<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeRequest;
use App\Http\Requests\SwapSubscriptionRequest;
use App\Http\Resources\ProductDetailResource;
use App\Models\License;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\InvalidRequestException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->paginate(12)
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'type' => $product->type->value,
                'type_label' => $product->type->getLabel(),
                'version' => $product->version,
            ]);

        return Inertia::render('products/index', [
            'products' => $products,
        ]);
    }

    public function show(Product $product): Response
    {
        if (! $product->is_active) {
            abort(404);
        }

        $product->load(['activePackages' => fn ($query) => $query->orderBy('sort_order')]);

        // Get current user's subscription for this product (if any)
        $currentSubscription = null;
        $user = Auth::user();

        if ($user) {
            // Find subscription for any package of this product
            // Subscription names use format: {product_slug}_{package_slug}
            foreach ($product->activePackages as $pkg) {
                $subscriptionName = "{$product->slug}_{$pkg->slug}";
                $subscription = $user->subscription($subscriptionName);

                // Check for active or incomplete payment states
                if ($subscription && ($subscription->active() || $subscription->hasIncompletePayment())) {
                    // Find the package by stripe_price
                    $subscribedPackage = Package::findByStripePrice($subscription->stripe_price) ?? $pkg;

                    $currentSubscription = [
                        'id' => $subscription->id,
                        'package_id' => $subscribedPackage->id,
                        'package_slug' => $subscribedPackage->slug,
                        'package_name' => $subscribedPackage->name,
                        'stripe_price' => $subscription->stripe_price,
                        'is_yearly' => $subscribedPackage->isYearlyPrice($subscription->stripe_price),
                        'ends_at' => $subscription->ends_at?->toISOString(),
                        'on_grace_period' => $subscription->onGracePeriod(),
                        'requires_payment' => $subscription->hasIncompletePayment(),
                    ];
                    break;
                }
            }
        }

        return Inertia::render('products/show', [
            'product' => (new ProductDetailResource($product))->resolve(),
            'currentSubscription' => $currentSubscription,
        ]);
    }

    public function subscribe(SubscribeRequest $request, Package $package): SymfonyResponse
    {
        // Ensure the package belongs to an active product
        if (! $package->product || ! $package->product->is_active || ! $package->is_active) {
            abort(404);
        }

        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $billingInterval = $request->validated('billing_interval');

        // Get the appropriate Stripe price ID based on billing interval
        $priceId = $billingInterval === 'yearly'
            ? $package->stripe_yearly_price_id
            : $package->stripe_monthly_price_id;

        if (! $priceId) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Pricing not available',
                'description' => 'This billing interval is not available for this package.',
            ]);

            return back();
        }

        // Create a unique subscription name using product and package slug to avoid collisions
        $subscriptionName = "{$package->product->slug}_{$package->slug}";

        // Check if user already has an active subscription for this product
        foreach ($package->product->activePackages as $pkg) {
            $existingName = "{$package->product->slug}_{$pkg->slug}";
            if ($user->subscribed($existingName)) {
                Inertia::flash('toast', [
                    'type' => 'warning',
                    'message' => 'Already subscribed',
                    'description' => 'You already have an active subscription for this product. Use swap to change plans.',
                ]);

                return redirect()->route('dashboard.subscriptions.index');
            }
        }

        // Create Stripe Checkout session with metadata on the subscription
        try {
            $checkout = $user->newSubscription($subscriptionName, $priceId)
                ->withMetadata([
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'product_id' => $package->product_id,
                    'product_name' => $package->product->name,
                ])
                ->checkout([
                    'success_url' => route('dashboard.subscriptions.index').'?checkout=success',
                    'cancel_url' => route('products.show', $package->product->slug).'?checkout=cancelled',
                ]);
        } catch (InvalidRequestException $e) {
            Log::error('Stripe checkout failed', [
                'package_id' => $package->id,
                'price_id' => $priceId,
                'error' => $e->getMessage(),
            ]);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Unable to process subscription',
                'description' => 'There was an issue with the pricing configuration. Please contact support.',
            ]);

            return back();
        }

        // Use Inertia::location() for external redirect to Stripe
        return Inertia::location($checkout->url);
    }

    public function swap(SwapSubscriptionRequest $request, Package $package): RedirectResponse
    {
        // Ensure the package belongs to an active product
        if (! $package->product || ! $package->product->is_active || ! $package->is_active) {
            abort(404);
        }

        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $billingInterval = $request->validated('billing_interval');

        // Get the appropriate Stripe price ID based on billing interval
        $newPriceId = $billingInterval === 'yearly'
            ? $package->stripe_yearly_price_id
            : $package->stripe_monthly_price_id;

        if (! $newPriceId) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Pricing not available',
                'description' => 'This billing interval is not available for this package.',
            ]);

            return back();
        }

        // Find the user's current subscription for this product
        // Subscription names use format: {product_slug}_{package_slug}
        $product = $package->product;
        $currentSubscription = null;

        foreach ($product->activePackages as $pkg) {
            $subscriptionName = "{$product->slug}_{$pkg->slug}";
            $subscription = $user->subscription($subscriptionName);
            if ($subscription && $subscription->active()) {
                $currentSubscription = $subscription;
                break;
            }
        }

        if (! $currentSubscription) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No active subscription',
                'description' => 'You don\'t have an active subscription for this product.',
            ]);

            return back();
        }

        // Check if they're trying to swap to the same package and same billing interval
        if ($currentSubscription->stripe_price === $newPriceId) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => 'No change needed',
                'description' => 'You are already on this plan.',
            ]);

            return back();
        }

        try {
            DB::transaction(function () use ($currentSubscription, $newPriceId, $package, $product) {
                // Find the current license
                $currentLicense = License::where('subscription_id', $currentSubscription->id)->first();

                // Swap the subscription to the new price
                $currentSubscription->swap($newPriceId);

                // Update subscription type to new package name
                $newSubscriptionName = "{$product->slug}_{$package->slug}";
                $currentSubscription->update(['type' => $newSubscriptionName]);

                // Update the license with new package info
                if ($currentLicense) {
                    $currentLicense->update([
                        'package_id' => $package->id,
                        'domain_limit' => $package->domain_limit,
                    ]);
                }
            });

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Subscription updated',
                'description' => 'Your subscription has been changed to '.$package->name.'.',
            ]);
        } catch (IncompletePayment $e) {
            // Handle SCA/3D Secure - redirect to Cashier's payment confirmation page
            Log::warning('Subscription swap requires payment confirmation', [
                'subscription_id' => $currentSubscription->id,
                'user_id' => $user->id,
                'payment_id' => $e->payment->id,
            ]);

            return redirect()->route('cashier.payment', [
                'id' => $e->payment->id,
                'redirect' => route('dashboard.subscriptions.index'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to swap subscription', [
                'subscription_id' => $currentSubscription->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Failed to update subscription',
                'description' => 'An error occurred while updating your subscription. Please try again.',
            ]);
        }

        return back();
    }
}
