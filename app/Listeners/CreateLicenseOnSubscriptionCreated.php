<?php

namespace App\Listeners;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Package;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Cashier\Subscription;

class CreateLicenseOnSubscriptionCreated implements ShouldQueue
{
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] !== 'customer.subscription.created') {
            return;
        }

        $stripeSubscription = $event->payload['data']['object'];
        $stripeSubscriptionId = $stripeSubscription['id'];

        // Find the local subscription record
        $subscription = Subscription::where('stripe_id', $stripeSubscriptionId)->first();

        if (! $subscription) {
            Log::warning('CreateLicenseOnSubscriptionCreated: Subscription not found', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);

            return;
        }

        // Check if license already exists for this subscription
        if (License::where('subscription_id', $subscription->id)->exists()) {
            return;
        }

        // Get metadata from checkout session via the subscription metadata
        $metadata = $stripeSubscription['metadata'] ?? [];
        $packageId = $metadata['package_id'] ?? null;
        $productId = $metadata['product_id'] ?? null;

        // Fallback: try to find package by subscription name (slug)
        if (! $packageId) {
            $package = Package::where('slug', $subscription->type)->first();
            if ($package) {
                $packageId = $package->id;
                $productId = $package->product_id;
            }
        }

        if (! $packageId || ! $productId) {
            Log::warning('CreateLicenseOnSubscriptionCreated: Package/Product not found', [
                'subscription_id' => $subscription->id,
                'subscription_type' => $subscription->type,
                'metadata' => $metadata,
            ]);

            return;
        }

        $package = Package::find($packageId);

        if (! $package) {
            Log::warning('CreateLicenseOnSubscriptionCreated: Package not found', [
                'package_id' => $packageId,
            ]);

            return;
        }

        // Find the user
        $user = User::find($subscription->user_id);

        if (! $user) {
            Log::warning('CreateLicenseOnSubscriptionCreated: User not found', [
                'user_id' => $subscription->user_id,
            ]);

            return;
        }

        // Create the license
        License::create([
            'user_id' => $user->id,
            'product_id' => $productId,
            'package_id' => $packageId,
            'subscription_id' => $subscription->id,
            'domain_limit' => $package->domain_limit,
            'status' => LicenseStatus::Active,
            'expires_at' => null, // Subscription-based, no fixed expiry
        ]);

        Log::info('CreateLicenseOnSubscriptionCreated: License created', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'package_id' => $packageId,
        ]);
    }
}
