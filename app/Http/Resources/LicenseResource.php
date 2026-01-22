<?php

namespace App\Http\Resources;

use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin License
 */
class LicenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'license_key' => $this->license_key,
            'domain_limit' => $this->domain_limit,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'expires_at' => $this->expires_at?->toISOString(),
            'last_validated_at' => $this->last_validated_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
            ],
            'package' => [
                'id' => $this->package->id,
                'name' => $this->package->name,
                'slug' => $this->package->slug,
            ],
            'activations_count' => $this->activations_count ?? $this->activations()->count(),
            'active_activations_count' => $this->active_activations_count ?? $this->activeActivations()->count(),
        ];
    }
}
