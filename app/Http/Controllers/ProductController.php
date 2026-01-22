<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductDetailResource;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

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

    public function subscribe(Package $package): RedirectResponse
    {
        // Ensure the package belongs to an active product
        if (! $package->product || ! $package->product->is_active || ! $package->is_active) {
            abort(404);
        }

        Inertia::flash('toast', [
            'type' => 'info',
            'message' => 'Subscription not available yet',
            'description' => 'Stripe integration is coming soon. Stay tuned!',
        ]);

        return back();
    }
}
