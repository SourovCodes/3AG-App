<?php

namespace App\Http\Middleware;

use App\Enums\LicenseStatus;
use App\Models\License;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateActiveLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $licenseKey = $request->input('license_key');
        $domain = $request->input('domain');

        if (empty($licenseKey) || empty($domain)) {
            return response()->json(['message' => 'License key and domain are required.'], 400);
        }

        $license = License::query()
            ->where('license_key', $licenseKey)
            ->first();

        if (! $license) {
            return response()->json(['message' => 'Invalid license key.'], 401);
        }

        if ($license->status !== LicenseStatus::Active) {
            return response()->json(['message' => 'License is not active.'], 403);
        }

        if ($license->expires_at !== null && $license->expires_at->isPast()) {
            return response()->json(['message' => 'License has expired.'], 403);
        }

        $normalizedDomain = $this->normalizeDomain($domain);

        $activation = $license->activations()
            ->where('domain', $normalizedDomain)
            ->whereNull('deactivated_at')
            ->first();

        if (! $activation) {
            return response()->json(['message' => 'License is not activated on this domain.'], 403);
        }

        $activation->updateLastChecked();

        $request->merge([
            'validated_license' => $license,
            'validated_activation' => $activation,
            'normalized_domain' => $normalizedDomain,
        ]);

        return $next($request);
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = explode('?', $domain)[0];
        $domain = preg_replace('#:\d+$#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);

        return strtolower(trim($domain));
    }
}
