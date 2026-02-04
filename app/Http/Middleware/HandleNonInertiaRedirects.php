<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Intercepts redirects to non-Inertia routes (like Filament admin panel)
 * and converts them to full page redirects to avoid Inertia modal issues.
 */
class HandleNonInertiaRedirects
{
    /**
     * Routes that are not handled by Inertia and require full page redirects.
     */
    protected array $nonInertiaRoutes = [
        '/admin',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only process Inertia requests that result in redirects
        if (! $request->header('X-Inertia') || ! $response instanceof RedirectResponse) {
            return $response;
        }

        $targetUrl = $response->getTargetUrl();

        // Check if redirect target is a non-Inertia route
        foreach ($this->nonInertiaRoutes as $route) {
            if (str_starts_with($targetUrl, url($route))) {
                return Inertia::location($targetUrl);
            }
        }

        return $response;
    }
}
