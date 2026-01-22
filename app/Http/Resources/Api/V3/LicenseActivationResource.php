<?php

namespace App\Http\Resources\Api\V3;

use App\Models\LicenseActivation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LicenseActivation
 */
class LicenseActivationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'is_active' => $this->isActive(),
            'activated_at' => $this->activated_at?->toISOString(),
            'deactivated_at' => $this->deactivated_at?->toISOString(),
            'last_checked_at' => $this->last_checked_at?->toISOString(),
        ];
    }
}
