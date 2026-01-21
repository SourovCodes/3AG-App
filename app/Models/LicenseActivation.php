<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseActivation extends Model
{
    protected $fillable = [
        'license_id',
        'domain',
        'ip_address',
        'user_agent',
        'last_checked_at',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
            'activated_at' => 'datetime',
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

    public function updateLastChecked(): void
    {
        $this->update(['last_checked_at' => now()]);
    }
}
