<?php

namespace App\Http\Resources;

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
            'license_id' => $this->license_id,
            'domain' => $this->domain,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'last_checked_at' => $this->last_checked_at?->toISOString(),
            'activated_at' => $this->activated_at?->toISOString(),
            'deactivated_at' => $this->deactivated_at?->toISOString(),
        ];
    }
}
