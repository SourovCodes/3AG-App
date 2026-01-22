<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeRequest;
use App\Http\Resources\ProductDetailResource;
use App\Models\Package;
use App\Models\Product;
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

        return Inertia::render('products/show', [
            'product' => (new ProductDetailResource($product))->resolve(),
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
}
