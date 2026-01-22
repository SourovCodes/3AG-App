<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'package_id',
        'subscription_id',
        'license_key',
        'domain_limit',
        'status',
        'expires_at',
        'last_validated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
            'domain_limit' => 'integer',
            'expires_at' => 'datetime',
            'last_validated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (License $license) {
            if (empty($license->license_key)) {
                $license->license_key = static::generateLicenseKey();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(\Laravel\Cashier\Subscription::class);
    }

    public function activations(): HasMany
    {
        return $this->hasMany(LicenseActivation::class);
    }

    public function activeActivations(): HasMany
    {
        return $this->activations()->whereNull('deactivated_at');
    }

    public function isActive(): bool
    {
        return $this->status === LicenseStatus::Active &&
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function canActivateMoreDomains(): bool
    {
        if ($this->domain_limit === null) {
            return true; // Unlimited
        }

        return $this->activeActivations()->count() < $this->domain_limit;
    }

    public function getRemainingActivations(): ?int
    {
        if ($this->domain_limit === null) {
            return null; // Unlimited
        }

        return max(0, $this->domain_limit - $this->activeActivations()->count());
    }

    public function suspend(): void
    {
        $this->update(['status' => LicenseStatus::Suspended]);
    }

    public function pause(): void
    {
        $this->update(['status' => LicenseStatus::Paused]);
    }

    public function activate(): void
    {
        $this->update(['status' => LicenseStatus::Active]);
    }

    public function cancel(): void
    {
        $this->update(['status' => LicenseStatus::Cancelled]);
    }

    public function expire(): void
    {
        $this->update(['status' => LicenseStatus::Expired]);
    }

    /**
     * Sync license status and expiry based on subscription state.
     *
     * @param  \Carbon\Carbon|null  $expiresAt  The subscription's current_period_end (pass from webhook to avoid API call)
     */
    public function syncStatusFromSubscription(?\Carbon\Carbon $expiresAt = null): void
    {
        if (! $this->subscription) {
            return;
        }

        $subscription = $this->subscription;

        // Update expires_at if provided from webhook
        if ($expiresAt !== null) {
            $this->update(['expires_at' => $expiresAt]);
        }

        // Order matters: check most terminal states first
        if ($subscription->ended()) {
            $this->cancel();

            return;
        }

        // Canceled but not on grace period = fully cancelled
        if ($subscription->canceled() && ! $subscription->onGracePeriod()) {
            $this->cancel();

            return;
        }

        // Incomplete payment requires user action (SCA, failed payment)
        if ($subscription->hasIncompletePayment()) {
            $this->suspend();

            return;
        }

        // Active subscription (includes grace period)
        if ($subscription->active() || $subscription->onGracePeriod()) {
            $this->activate();

            return;
        }

        // Fallback: past_due, unpaid, incomplete, etc.
        $this->suspend();
    }

    public static function generateLicenseKey(): string
    {
        return strtoupper(implode('-', [
            Str::random(8),
            Str::random(8),
            Str::random(8),
            Str::random(8),
        ]));
    }
}
