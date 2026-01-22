<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V3\ActivateLicenseRequest;
use App\Http\Requests\Api\V3\CheckLicenseRequest;
use App\Http\Requests\Api\V3\DeactivateLicenseRequest;
use App\Http\Requests\Api\V3\ValidateLicenseRequest;
use App\Http\Resources\Api\V3\LicenseValidationResource;
use App\Models\License;
use Illuminate\Http\JsonResponse;

class LicenseController extends Controller
{
    /**
     * Validate a license key and return license information.
     */
    public function validate(ValidateLicenseRequest $request): JsonResponse
    {
        $license = License::query()
            ->where('license_key', $request->validated('license_key'))
            ->whereHas('product', fn ($query) => $query->where('slug', $request->validated('product_slug')))
            ->with(['product', 'package'])
            ->first();

        if (! $license) {
            return $this->errorResponse('License key not found for this product.', 'license_not_found', 404);
        }

        $license->update(['last_validated_at' => now()]);

        return response()->json([
            'success' => true,
            'license' => new LicenseValidationResource($license),
        ]);
    }

    /**
     * Activate a license on a domain.
     */
    public function activate(ActivateLicenseRequest $request): JsonResponse
    {
        $license = License::query()
            ->where('license_key', $request->validated('license_key'))
            ->whereHas('product', fn ($query) => $query->where('slug', $request->validated('product_slug')))
            ->with(['product', 'package'])
            ->first();

        if (! $license) {
            return $this->errorResponse('License key not found for this product.', 'license_not_found', 404);
        }

        if (! $license->isActive()) {
            return $this->errorResponse(
                'License is not active. Current status: '.$license->status->getLabel(),
                'license_inactive',
                403
            );
        }

        $domain = $this->normalizeDomain($request->validated('domain'));

        // Check if already activated on this domain
        $existingActivation = $license->activations()
            ->where('domain', $domain)
            ->first();

        if ($existingActivation) {
            // If already active, update last checked and return success
            if ($existingActivation->isActive()) {
                $existingActivation->updateLastChecked();

                return response()->json([
                    'success' => true,
                    'message' => 'License already activated on this domain.',
                    'license' => new LicenseValidationResource($license),
                ]);
            }

            // If deactivated, reactivate it
            $existingActivation->reactivate();

            return response()->json([
                'success' => true,
                'message' => 'License reactivated on this domain.',
                'license' => new LicenseValidationResource($license->fresh(['product', 'package'])),
            ]);
        }

        // Check domain limit
        if (! $license->canActivateMoreDomains()) {
            return $this->errorResponse(
                'Domain limit reached. Maximum '.$license->domain_limit.' domain(s) allowed.',
                'domain_limit_reached',
                403
            );
        }

        // Create new activation
        $activation = $license->activations()->create([
            'domain' => $domain,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_checked_at' => now(),
        ]);

        $license->update(['last_validated_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'License activated successfully.',
            'license' => new LicenseValidationResource($license->fresh(['product', 'package'])),
        ], 201);
    }

    /**
     * Deactivate a license from a domain.
     */
    public function deactivate(DeactivateLicenseRequest $request): JsonResponse
    {
        $license = License::query()
            ->where('license_key', $request->validated('license_key'))
            ->whereHas('product', fn ($query) => $query->where('slug', $request->validated('product_slug')))
            ->first();

        if (! $license) {
            return $this->errorResponse('License key not found for this product.', 'license_not_found', 404);
        }

        $domain = $this->normalizeDomain($request->validated('domain'));

        $activation = $license->activations()
            ->where('domain', $domain)
            ->whereNull('deactivated_at')
            ->first();

        if (! $activation) {
            return $this->errorResponse(
                'No active activation found for this domain.',
                'activation_not_found',
                404
            );
        }

        $activation->deactivate();

        return response()->json([
            'success' => true,
            'message' => 'License deactivated successfully.',
        ]);
    }

    /**
     * Check if a license is active on a specific domain.
     */
    public function check(CheckLicenseRequest $request): JsonResponse
    {
        $license = License::query()
            ->where('license_key', $request->validated('license_key'))
            ->whereHas('product', fn ($query) => $query->where('slug', $request->validated('product_slug')))
            ->with(['product', 'package'])
            ->first();

        if (! $license) {
            return $this->errorResponse('License key not found for this product.', 'license_not_found', 404);
        }

        $domain = $this->normalizeDomain($request->validated('domain'));

        $activation = $license->activations()
            ->where('domain', $domain)
            ->whereNull('deactivated_at')
            ->first();

        if (! $activation) {
            return response()->json([
                'success' => true,
                'activated' => false,
                'license_valid' => $license->isActive(),
            ]);
        }

        // Update last checked timestamp
        $activation->updateLastChecked();
        $license->update(['last_validated_at' => now()]);

        return response()->json([
            'success' => true,
            'activated' => true,
            'license_valid' => $license->isActive(),
            'license' => new LicenseValidationResource($license),
        ]);
    }

    /**
     * Normalize domain by removing protocol and trailing slashes.
     */
    private function normalizeDomain(string $domain): string
    {
        // Remove protocol
        $domain = preg_replace('#^https?://#', '', $domain);

        // Remove trailing slashes and paths
        $domain = explode('/', $domain)[0];

        // Remove www prefix
        $domain = preg_replace('#^www\.#', '', $domain);

        return strtolower(trim($domain));
    }

    /**
     * Return a standardized error response.
     */
    private function errorResponse(string $message, string $code, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
            ],
        ], $status);
    }
}
