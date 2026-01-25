<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V3\CheckUpdateRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
{
    /**
     * Check for product updates.
     */
    public function check(CheckUpdateRequest $request): JsonResponse
    {
        $product = Product::query()
            ->where('slug', $request->validated('product_slug'))
            ->where('is_active', true)
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'version' => $product->version,
                'download_url' => $product->download_url,
            ],
        ]);
    }
}
