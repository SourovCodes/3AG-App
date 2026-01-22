<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseActivation extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'domain',
        'ip_address',
        'user_agent',
        'last_checked_at',
        'activated_at',
        'deactivated_at',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LicenseActivation $activation) {
            if (empty($activation->activated_at)) {
                $activation->activated_at = now();
            }
        });
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deactivated_at');
    }

    public function scopeDeactivated(Builder $query): Builder
    {
        return $query->whereNotNull('deactivated_at');
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    public function updateLastChecked(): void
    {
        $this->update(['last_checked_at' => now()]);
    }

    public function deactivate(): void
    {
        $this->update(['deactivated_at' => now()]);
    }

    public function reactivate(): void
    {
        $this->update([
            'deactivated_at' => null,
            'activated_at' => now(),
        ]);
    }
}
