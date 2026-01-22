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
    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int>
     */
    public array $backoff = [10, 30, 60, 120];

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
            Log::warning('CreateLicenseOnSubscriptionCreated: Subscription not found, will retry', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);

            // Throw exception to trigger retry - subscription record may not exist yet
            throw new \RuntimeException("Subscription {$stripeSubscriptionId} not found yet, will retry");
        }

        // Check if license already exists for this subscription
        if (License::where('subscription_id', $subscription->id)->exists()) {
            return;
        }

        // Get stripe_price from subscription items
        $stripePriceId = $stripeSubscription['items']['data'][0]['price']['id'] ?? null;

        // Primary: Find package by stripe_price
        $package = $stripePriceId ? Package::findByStripePrice($stripePriceId) : null;

        // Fallback: Use metadata from checkout session
        if (! $package) {
            $metadata = $stripeSubscription['metadata'] ?? [];
            $packageId = $metadata['package_id'] ?? null;

            if ($packageId) {
                $package = Package::find($packageId);
            }
        }

        // Fallback: Try to find package by subscription name (slug)
        if (! $package) {
            $package = Package::where('slug', $subscription->type)->first();
        }

        if (! $package) {
            Log::warning('CreateLicenseOnSubscriptionCreated: Package not found', [
                'subscription_id' => $subscription->id,
                'subscription_type' => $subscription->type,
                'stripe_price_id' => $stripePriceId,
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
            'product_id' => $package->product_id,
            'package_id' => $package->id,
            'subscription_id' => $subscription->id,
            'domain_limit' => $package->domain_limit,
            'status' => LicenseStatus::Active,
            'expires_at' => null, // Subscription-based, no fixed expiry
        ]);

        Log::info('CreateLicenseOnSubscriptionCreated: License created', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'package_id' => $package->id,
        ]);
    }
}
