<?php

namespace App\Http\Resources\Api\V3;

use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin License
 */
class LicenseValidationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'valid' => $this->isActive(),
            'expires_at' => $this->expires_at?->toISOString(),
            'domain_limit' => $this->domain_limit,
            'domains_used' => $this->activeActivations()->count(),
            'product' => $this->product->name,
            'package' => $this->package->name,
            'version' => $this->product->version,
        ];
    }
}
