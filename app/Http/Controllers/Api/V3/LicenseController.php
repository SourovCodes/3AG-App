<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Api\V3\Concerns\NormalizesDomain;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V3\ActivateLicenseRequest;
use App\Http\Requests\Api\V3\CheckLicenseRequest;
use App\Http\Requests\Api\V3\DeactivateLicenseRequest;
use App\Http\Requests\Api\V3\ValidateLicenseRequest;
use App\Http\Resources\Api\V3\LicenseValidationResource;
use App\Models\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class LicenseController extends Controller
{
    use NormalizesDomain;

    public function validate(ValidateLicenseRequest $request): JsonResponse
    {
        $license = $this->findLicense(
            $request->validated('license_key'),
            $request->validated('product_slug')
        );

        if (! $license) {
            return response()->json(['message' => 'Invalid license key.'], 404);
        }

        $license->update(['last_validated_at' => now()]);

        return response()->json([
            'data' => new LicenseValidationResource($license),
        ]);
    }

    public function activate(ActivateLicenseRequest $request): JsonResponse
    {
        $license = $this->findLicense(
            $request->validated('license_key'),
            $request->validated('product_slug')
        );

        if (! $license) {
            return response()->json(['message' => 'Invalid license key.'], 404);
        }

        if (! $license->isActive()) {
            return response()->json(['message' => 'License is not active.'], 403);
        }

        $domain = $this->normalizeDomain($request->validated('domain'));

        $existingActivation = $license->activations()
            ->where('domain', $domain)
            ->first();

        if ($existingActivation) {
            if ($existingActivation->isActive()) {
                $existingActivation->updateLastChecked();

                return response()->json([
                    'data' => new LicenseValidationResource($license),
                ]);
            }

            $existingActivation->reactivate();

            return response()->json([
                'data' => new LicenseValidationResource(
                    $license->fresh(['product', 'package'])
                        ->loadCount(['activations as domains_used' => fn ($q) => $q->whereNull('deactivated_at')])
                ),
            ]);
        }

        if (! $license->canActivateMoreDomains()) {
            return response()->json([
                'message' => "Domain limit reached. Maximum {$license->domain_limit} domain(s) allowed.",
            ], 403);
        }

        DB::transaction(function () use ($license, $domain, $request) {
            $license->activations()->create([
                'domain' => $domain,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_checked_at' => now(),
            ]);

            $license->update(['last_validated_at' => now()]);
        });

        return response()->json([
            'data' => new LicenseValidationResource(
                $license->fresh(['product', 'package'])
                    ->loadCount(['activations as domains_used' => fn ($q) => $q->whereNull('deactivated_at')])
            ),
        ], 201);
    }

    public function deactivate(DeactivateLicenseRequest $request): JsonResponse|Response
    {
        $license = $this->findLicense(
            $request->validated('license_key'),
            $request->validated('product_slug'),
            withRelations: false
        );

        if (! $license) {
            return response()->json(['message' => 'Invalid license key.'], 404);
        }

        $domain = $this->normalizeDomain($request->validated('domain'));

        $activation = $license->activations()
            ->where('domain', $domain)
            ->whereNull('deactivated_at')
            ->first();

        if (! $activation) {
            return response()->json(['message' => 'No active activation found for this domain.'], 404);
        }

        $activation->deactivate();

        return response()->noContent();
    }

    public function check(CheckLicenseRequest $request): JsonResponse
    {
        $license = $this->findLicense(
            $request->validated('license_key'),
            $request->validated('product_slug')
        );

        if (! $license) {
            return response()->json(['message' => 'Invalid license key.'], 404);
        }

        $domain = $this->normalizeDomain($request->validated('domain'));

        $activation = $license->activations()
            ->where('domain', $domain)
            ->whereNull('deactivated_at')
            ->first();

        if (! $activation) {
            return response()->json([
                'data' => [
                    'activated' => false,
                ],
            ]);
        }

        $activation->updateLastChecked();
        $license->update(['last_validated_at' => now()]);

        return response()->json([
            'data' => [
                'activated' => true,
                'license' => new LicenseValidationResource($license),
            ],
        ]);
    }

    private function findLicense(string $licenseKey, string $productSlug, bool $withRelations = true): ?License
    {
        $query = License::query()
            ->where('license_key', $licenseKey)
            ->whereHas('product', fn ($q) => $q->where('slug', $productSlug)->where('is_active', true));

        if ($withRelations) {
            $query->with(['product', 'package'])
                ->withCount(['activations as domains_used' => fn ($q) => $q->whereNull('deactivated_at')]);
        }

        return $query->first();
    }
}
