<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeRequest;
use App\Http\Requests\SwapSubscriptionRequest;
use App\Http\Resources\ProductDetailResource;
use App\Models\License;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
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
            // Find active subscription for any package of this product
            $packageSlugs = $product->activePackages->pluck('slug')->toArray();

            foreach ($packageSlugs as $slug) {
                $subscription = $user->subscription($slug);
                if ($subscription && $subscription->active()) {
                    // Get the license to find the package
                    $license = License::where('subscription_id', $subscription->id)
                        ->with('package')
                        ->first();

                    $currentSubscription = [
                        'id' => $subscription->id,
                        'package_id' => $license?->package_id,
                        'package_slug' => $slug,
                        'package_name' => $license?->package?->name ?? $slug,
                        'stripe_price' => $subscription->stripe_price,
                        'is_yearly' => $license?->package?->stripe_yearly_price_id === $subscription->stripe_price,
                        'ends_at' => $subscription->ends_at?->toISOString(),
                        'on_grace_period' => $subscription->onGracePeriod(),
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

        // Create a unique subscription name using package slug
        $subscriptionName = $package->slug;

        // Check if user already has an active subscription for this package
        if ($user->subscribed($subscriptionName)) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => 'Already subscribed',
                'description' => 'You already have an active subscription for this package.',
            ]);

            return redirect()->route('dashboard.subscriptions.index');
        }

        // Create Stripe Checkout session
        $checkout = $user->newSubscription($subscriptionName, $priceId)
            ->checkout([
                'success_url' => route('dashboard.subscriptions.index').'?checkout=success',
                'cancel_url' => route('products.show', $package->product->slug).'?checkout=cancelled',
                'metadata' => [
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'product_id' => $package->product_id,
                    'product_name' => $package->product->name,
                ],
            ]);

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
        $product = $package->product;
        $packageSlugs = $product->activePackages->pluck('slug')->toArray();
        $currentSubscription = null;
        $currentSubscriptionName = null;

        foreach ($packageSlugs as $slug) {
            $subscription = $user->subscription($slug);
            if ($subscription && $subscription->active()) {
                $currentSubscription = $subscription;
                $currentSubscriptionName = $slug;
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
            // Find the current license
            $currentLicense = License::where('subscription_id', $currentSubscription->id)->first();

            // Swap the subscription to the new price
            // This handles both package changes and billing interval changes
            $currentSubscription->swap($newPriceId);

            // Update the license with new package info
            if ($currentLicense) {
                $currentLicense->update([
                    'package_id' => $package->id,
                    'domain_limit' => $package->domain_limit,
                ]);
            }

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Subscription updated',
                'description' => 'Your subscription has been changed to '.$package->name.'.',
            ]);
        } catch (\Exception $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Failed to update subscription',
                'description' => $e->getMessage(),
            ]);
        }

        return back();
    }
}
