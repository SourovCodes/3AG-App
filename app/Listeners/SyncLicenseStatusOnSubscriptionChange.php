<?php

namespace App\Listeners;

use App\Models\License;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class SyncLicenseStatusOnSubscriptionChange implements ShouldQueue
{
    /**
     * The Stripe webhook events that trigger license sync.
     * Note: customer.subscription.created is handled by CreateLicenseOnSubscriptionCreated
     */
    private const SUBSCRIPTION_EVENTS = [
        'customer.subscription.updated',
        'customer.subscription.deleted',
        'invoice.payment_failed',
    ];

    /**
     * Handle the Cashier webhook event.
     */
    public function handle(WebhookReceived $event): void
    {
        if (! in_array($event->payload['type'], self::SUBSCRIPTION_EVENTS)) {
            return;
        }

        $stripeSubscriptionId = $this->extractSubscriptionId($event->payload);

        if (! $stripeSubscriptionId) {
            Log::warning('SyncLicenseStatusOnSubscriptionChange: Could not extract subscription ID', [
                'event_type' => $event->payload['type'],
            ]);

            return;
        }

        // Extract current_period_end from webhook payload
        $currentPeriodEnd = $this->extractCurrentPeriodEnd($event->payload);

        // Find all licenses linked to this Stripe subscription
        $licenses = License::whereHas('subscription', function ($query) use ($stripeSubscriptionId) {
            $query->where('stripe_id', $stripeSubscriptionId);
        })->get();

        if ($licenses->isEmpty()) {
            Log::info('SyncLicenseStatusOnSubscriptionChange: No licenses found for subscription', [
                'stripe_subscription_id' => $stripeSubscriptionId,
                'event_type' => $event->payload['type'],
            ]);

            return;
        }

        foreach ($licenses as $license) {
            $previousStatus = $license->status;

            $license->refresh(); // Ensure we have fresh subscription data
            $license->syncStatusFromSubscription($currentPeriodEnd);

            Log::info('SyncLicenseStatusOnSubscriptionChange: License status synced', [
                'license_id' => $license->id,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'event_type' => $event->payload['type'],
                'previous_status' => $previousStatus->value,
                'new_status' => $license->fresh()->status->value,
                'expires_at' => $currentPeriodEnd?->toISOString(),
            ]);
        }
    }

    /**
     * Extract the Stripe subscription ID from the webhook payload.
     */
    private function extractSubscriptionId(array $payload): ?string
    {
        $object = $payload['data']['object'] ?? [];

        // For subscription events, the object IS the subscription
        if (str_starts_with($payload['type'], 'customer.subscription.')) {
            return $object['id'] ?? null;
        }

        // For invoice events, the subscription ID is a property
        if (str_starts_with($payload['type'], 'invoice.')) {
            return $object['subscription'] ?? null;
        }

        return null;
    }

    /**
     * Extract current_period_end timestamp from the webhook payload.
     */
    private function extractCurrentPeriodEnd(array $payload): ?Carbon
    {
        $object = $payload['data']['object'] ?? [];

        // For subscription events, get from items or top level
        if (str_starts_with($payload['type'], 'customer.subscription.')) {
            $periodEnd = $object['items']['data'][0]['current_period_end']
                ?? $object['current_period_end']
                ?? null;

            return $periodEnd ? Carbon::createFromTimestamp($periodEnd) : null;
        }

        // For invoice events, get from lines (subscription line items)
        if (str_starts_with($payload['type'], 'invoice.')) {
            $periodEnd = $object['lines']['data'][0]['period']['end'] ?? null;

            return $periodEnd ? Carbon::createFromTimestamp($periodEnd) : null;
        }

        return null;
    }
}
