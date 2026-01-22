<?php

namespace App\Http\Resources;

use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
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
        // Find package by stripe_price (uses unique index)
        $package = Package::findByStripePrice($this->stripe_price)?->load('product');

        // Get current period end from Stripe (next billing date)
        $currentPeriodEnd = null;
        if ($this->stripe_id && $this->active()) {
            try {
                $stripeSubscription = $this->asStripeSubscription();
                // current_period_end may be at top level or in items
                $periodEnd = $stripeSubscription->current_period_end
                    ?? $stripeSubscription->items->data[0]->current_period_end
                    ?? null;

                if ($periodEnd !== null) {
                    $currentPeriodEnd = Carbon::createFromTimestamp($periodEnd)->toISOString();
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch Stripe subscription data', [
                    'subscription_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'id' => $this->id,
            'stripe_id' => $this->stripe_id,
            'stripe_status' => $this->stripe_status,
            'stripe_price' => $this->stripe_price,
            'quantity' => $this->quantity,
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'current_period_end' => $currentPeriodEnd,
            'product_name' => $package?->product?->name,
            'package_name' => $package?->name,
            'is_active' => $this->active(),
            'is_on_trial' => $this->onTrial(),
            'is_canceled' => $this->canceled(),
            'is_on_grace_period' => $this->onGracePeriod(),
        ];
    }
}
