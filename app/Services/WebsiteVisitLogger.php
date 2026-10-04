<?php

namespace App\Services;

use App\Http\Controllers\Frontend\StorefrontController;
use App\System;
use App\WebsiteVisitLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebsiteVisitLogger
{
    /**
     * Common bot / crawler signatures.
     *
     * @var array<int, string>
     */
    protected array $botPatterns = [
        'bot', 'crawl', 'spider', 'slurp', 'mediapartners', 'facebookexternalhit',
        'bingpreview', 'yandex', 'baidu', 'duckduckbot', 'applebot', 'semrush',
        'ahrefs', 'mj12bot', 'dotbot', 'petalbot', 'bytespider', 'gptbot',
        'claudebot', 'anthropic', 'chatgpt', 'googlebot', 'adsbot', 'apis-google',
        'feedfetcher', 'linkedinbot', 'twitterbot', 'telegrambot',
        'discordbot', 'pingdom', 'uptimerobot', 'headlesschrome', 'phantomjs',
        'python-requests', 'curl/', 'wget/', 'httpclient', 'go-http-client',
        'scrapy', 'node-fetch', 'axios/',
    ];

    public function shouldLog(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return false;
        }

        $path = ltrim($request->path(), '/');

        if (Str::startsWith($path, ['store/visit-logs', 'livewire', '_debugbar', 'telescope'])) {
            return false;
        }

        return $path === '' || $request->is('/') || $request->is('store') || $request->is('store/*');
    }

    public function log(Request $request): ?WebsiteVisitLog
    {
        if (! $this->shouldLog($request)) {
            return null;
        }

        $businessId = $this->resolveBusinessId($request);
        if ($businessId <= 0) {
            return null;
        }

        $userAgent = (string) $request->userAgent();
        [$isBot, $botName] = $this->detectBot($userAgent);

        if (! $this->shouldLogVisitorType($isBot)) {
            return null;
        }

        $pageMeta = $this->resolvePageMeta($request);
        $ip = app(ClientIpResolver::class)->resolve($request);
        $countryHint = $request->headers->get('CF-IPCountry')
            ?: $request->headers->get('CloudFront-Viewer-Country');
        if ($countryHint && strtoupper($countryHint) === 'XX') {
            $countryHint = null;
        }

        $geo = $this->resolveGeoForIp($ip, $countryHint);

        $log = WebsiteVisitLog::create(array_merge([
            'business_id' => $businessId,
            'visit_token' => (string) Str::uuid(),
            'ip_address' => $ip,
            'user_agent' => Str::limit($userAgent, 1000, ''),
            'is_bot' => $isBot,
            'bot_name' => $botName,
            'page_url' => Str::limit($request->fullUrl(), 2000, ''),
            'page_path' => Str::limit('/'.ltrim($request->path(), '/'), 1000, ''),
            'page_title' => null,
            'page_type' => $pageMeta['page_type'],
            'product_id' => $pageMeta['product_id'],
            'product_name' => $pageMeta['product_name'],
            'product_source' => $pageMeta['product_source'],
            'referer' => Str::limit((string) $request->headers->get('referer'), 2000, '') ?: null,
            'time_spent_seconds' => 0,
            'events' => [],
            'events_count' => 0,
            'last_activity_at' => now(),
        ], $geo));

        // Always enrich after response when city is missing (even if CF country code was set).
        if (empty($log->city) && ! empty($ip)) {
            $logId = (int) $log->id;
            dispatch(function () use ($logId, $ip, $countryHint) {
                app(IpGeolocationService::class)->fillVisitLog($logId, $ip, $countryHint);
            })->afterResponse();
        }

        return $log;
    }

    /**
     * Logging mode from website-logs settings page: humans | bots | both.
     */
    public static function visitorLogMode(): string
    {
        $mode = (string) (System::getProperty('website_logs_visitor_mode') ?: 'both');

        return in_array($mode, ['humans', 'bots', 'both'], true) ? $mode : 'both';
    }

    protected function shouldLogVisitorType(bool $isBot): bool
    {
        $mode = self::visitorLogMode();

        if ($mode === 'humans') {
            return ! $isBot;
        }

        if ($mode === 'bots') {
            return $isBot;
        }

        return true;
    }

    /**
     * Resolve location for an IP immediately when possible.
     *
     * @return array<string, mixed>
     */
    protected function resolveGeoForIp(?string $ip, ?string $countryHint): array
    {
        $empty = [
            'country' => null,
            'country_code' => null,
            'region' => null,
            'city' => null,
            'latitude' => null,
            'longitude' => null,
            'location_label' => null,
        ];

        $ip = trim((string) $ip);
        if ($ip === '') {
            return $empty;
        }

        // Lookup synchronously; providers are cached and have short timeouts.
        try {
            $geo = app(IpGeolocationService::class)->lookup($ip, $countryHint);
            if (! empty($geo['location_label']) || ! empty($geo['city']) || ! empty($geo['country'])) {
                return $geo;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        if ($countryHint) {
            $code = Str::upper(Str::limit($countryHint, 8, ''));

            return array_merge($empty, [
                'country_code' => $code,
                'country' => $code,
                'location_label' => $code,
            ]);
        }

        return $empty;
    }

    /**
     * @return array{0: bool, 1: string|null}
     */
    public function detectBot(?string $userAgent): array
    {
        $ua = strtolower(trim((string) $userAgent));
        if ($ua === '') {
            return [true, 'unknown'];
        }

        foreach ($this->botPatterns as $pattern) {
            if (str_contains($ua, $pattern)) {
                return [true, Str::limit($pattern, 100, '')];
            }
        }

        return [false, null];
    }

    /**
     * @return array{page_type: string, product_id: int|null, product_name: string|null, product_source: string|null}
     */
    public function resolvePageMeta(Request $request): array
    {
        $path = trim($request->path(), '/');
        $segments = $path === '' ? [] : explode('/', $path);

        $meta = [
            'page_type' => 'other',
            'product_id' => null,
            'product_name' => null,
            'product_source' => null,
        ];

        if ($path === '' || $path === '/') {
            $meta['page_type'] = 'home';

            return $meta;
        }

        if (($segments[0] ?? null) !== 'store') {
            return $meta;
        }

        $second = $segments[1] ?? null;
        $third = $segments[2] ?? null;
        $fourth = $segments[3] ?? null;

        if ($second === null) {
            $meta['page_type'] = 'home';

            return $meta;
        }

        if ($second === 'products' && $third === null) {
            $meta['page_type'] = 'products';

            return $meta;
        }

        if ($second === 'products' && is_numeric($third)) {
            $meta['page_type'] = 'product';
            $meta['product_id'] = (int) $third;
            $meta['product_source'] = 'local';

            return $meta;
        }

        if ($second === 'tab3een' && $third === 'catalog') {
            $meta['page_type'] = 'tab3een_catalog';

            return $meta;
        }

        if ($second === 'tab3een' && $third === 'products' && is_numeric($fourth)) {
            $meta['page_type'] = 'tab3een_product';
            $meta['product_id'] = (int) $fourth;
            $meta['product_source'] = 'tab3een';

            return $meta;
        }

        $map = [
            'categories' => 'categories',
            'flash-deals' => 'flash_deals',
            'search' => 'search',
            'pages' => 'page',
            'checkout' => 'checkout',
            'account' => 'account',
            'login' => 'auth',
            'register' => 'auth',
            'password' => 'auth',
        ];

        if (isset($map[$second])) {
            $meta['page_type'] = $map[$second];
        }

        return $meta;
    }

    public function resolveBusinessId(Request $request): int
    {
        $configured = (int) config('storefront.business_id', 0);
        if ($configured > 0) {
            return $configured;
        }

        try {
            return (int) StorefrontController::resolveBusinessId($request);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
