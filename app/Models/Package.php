<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'slug',
        'description',
        'domain_limit',
        'stripe_monthly_price_id',
        'stripe_yearly_price_id',
        'monthly_price',
        'yearly_price',
        'is_active',
        'sort_order',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'domain_limit' => 'integer',
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'is_active' => 'boolean',
            'features' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isUnlimited(): bool
    {
        return $this->domain_limit === null;
    }

    public function hasMonthlyPricing(): bool
    {
        return $this->stripe_monthly_price_id !== null;
    }

    public function hasYearlyPricing(): bool
    {
        return $this->stripe_yearly_price_id !== null;
    }

    /**
     * Find a package by its Stripe price ID (monthly or yearly).
     */
    public static function findByStripePrice(string $stripePriceId): ?self
    {
        return static::query()
            ->where('stripe_monthly_price_id', $stripePriceId)
            ->orWhere('stripe_yearly_price_id', $stripePriceId)
            ->first();
    }

    /**
     * Check if the given price ID is the yearly price for this package.
     */
    public function isYearlyPrice(string $stripePriceId): bool
    {
        return $this->stripe_yearly_price_id === $stripePriceId;
    }
}
