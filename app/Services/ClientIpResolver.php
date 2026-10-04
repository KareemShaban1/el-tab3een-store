<?php

namespace App\Services;

use Illuminate\Http\Request;

class ClientIpResolver
{
    /**
     * Resolve the real visitor IP behind Cloudflare / proxies / load balancers.
     */
    public function resolve(Request $request): ?string
    {
        $candidates = [
            $request->headers->get('CF-Connecting-IP'),
            $request->headers->get('True-Client-IP'),
            $request->headers->get('X-Real-IP'),
            $this->firstPublicIpFromList($request->headers->get('X-Forwarded-For')),
            $request->ip(),
            $request->server->get('REMOTE_ADDR'),
        ];

        foreach ($candidates as $candidate) {
            $ip = $this->normalizeIp($candidate);
            if ($ip !== null) {
                return $ip;
            }
        }

        return null;
    }

    protected function firstPublicIpFromList(?string $list): ?string
    {
        if ($list === null || trim($list) === '') {
            return null;
        }

        foreach (explode(',', $list) as $part) {
            $ip = $this->normalizeIp($part);
            if ($ip !== null && $this->isPublicIp($ip)) {
                return $ip;
            }
        }

        // Fall back to first valid IP even if private.
        foreach (explode(',', $list) as $part) {
            $ip = $this->normalizeIp($part);
            if ($ip !== null) {
                return $ip;
            }
        }

        return null;
    }

    protected function normalizeIp(?string $value): ?string
    {
        $ip = trim((string) $value);
        if ($ip === '') {
            return null;
        }

        // Strip port from IPv4 "1.2.3.4:1234"
        if (substr_count($ip, ':') === 1 && str_contains($ip, '.')) {
            $ip = explode(':', $ip, 2)[0];
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ?: null;
    }

    protected function isPublicIp(string $ip): bool
    {
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
