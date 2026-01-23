<?php

namespace App\Http\Controllers\Api\V3\Concerns;

trait NormalizesDomain
{
    protected function normalizeDomain(string $domain): string
    {
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = explode('?', $domain)[0];
        $domain = preg_replace('#:\d+$#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);

        return strtolower(trim($domain));
    }
}
