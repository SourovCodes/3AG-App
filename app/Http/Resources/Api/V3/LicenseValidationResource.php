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
            'license_key' => $this->license_key,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'is_active' => $this->isActive(),
            'domain_limit' => $this->domain_limit,
            'active_domains' => $this->activeActivations()->count(),
            'remaining_activations' => $this->getRemainingActivations(),
            'expires_at' => $this->expires_at?->toISOString(),
            'product' => [
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'version' => $this->product->version,
            ],
            'package' => [
                'name' => $this->package->name,
                'slug' => $this->package->slug,
            ],
        ];
    }
}
