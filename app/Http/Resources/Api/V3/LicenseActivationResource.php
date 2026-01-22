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
            'domain' => $this->domain,
            'activated_at' => $this->activated_at?->toISOString(),
        ];
    }
}
