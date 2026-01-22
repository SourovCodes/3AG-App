<?php

namespace App\Http\Resources;

use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Cashier\Subscription;

/**
 * @mixin Subscription
 */
class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Try to find associated license for product/package info
        $license = License::query()
            ->where('subscription_id', $this->id)
            ->with(['product', 'package'])
            ->first();

        return [
            'id' => $this->id,
            'stripe_id' => $this->stripe_id,
            'stripe_status' => $this->stripe_status,
            'stripe_price' => $this->stripe_price,
            'quantity' => $this->quantity,
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'product_name' => $license?->product?->name,
            'package_name' => $license?->package?->name,
            'is_active' => $this->active(),
            'is_on_trial' => $this->onTrial(),
            'is_canceled' => $this->canceled(),
            'is_on_grace_period' => $this->onGracePeriod(),
        ];
    }
}
