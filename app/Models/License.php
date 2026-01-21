<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class License extends Model
{
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

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function canActivateMoreDomains(): bool
    {
        if ($this->domain_limit === null) {
            return true; // Unlimited
        }

        return $this->activations()->count() < $this->domain_limit;
    }

    public function getRemainingActivations(): ?int
    {
        if ($this->domain_limit === null) {
            return null; // Unlimited
        }

        return max(0, $this->domain_limit - $this->activations()->count());
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
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
