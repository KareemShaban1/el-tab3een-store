<?php

namespace App\Services;

use App\WebsiteVisitLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IpGeolocationService
{
    private const CACHE_PREFIX = 'ip_geo_v2:';

    /**
     * @return array{
     *     country: ?string,
     *     country_code: ?string,
     *     region: ?string,
     *     city: ?string,
     *     latitude: ?float,
     *     longitude: ?float,
     *     location_label: ?string
     * }
     */
    public function lookup(?string $ip, ?string $countryHint = null): array
    {
        $empty = $this->emptyResult();

        $ip = trim((string) $ip);
        if ($ip === '' || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return $empty;
        }

        if ($this->isPrivateIp($ip)) {
            return array_merge($empty, [
                'country' => __('website_logs.local_network'),
                'location_label' => __('website_logs.local_network'),
            ]);
        }

        $cacheKey = self::CACHE_PREFIX.md5($ip);
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['location_label'])) {
            return $cached;
        }

        $result = $this->lookupFromProviders($ip);

        if (! empty($result['location_label']) || ! empty($result['city']) || ! empty($result['country'])) {
            Cache::put($cacheKey, $result, now()->addDays(14));

            return $result;
        }

        if ($countryHint && strtoupper($countryHint) !== 'XX') {
            $code = Str::upper(Str::limit($countryHint, 8, ''));
            $hinted = array_merge($empty, [
                'country_code' => $code,
                'country' => $code,
                'location_label' => $code,
            ]);
            // Short cache for hint-only so we retry full lookup later.
            Cache::put($cacheKey, $hinted, now()->addHours(6));

            return $hinted;
        }

        // Do not cache hard failures for long.
        Cache::put($cacheKey, $empty, now()->addMinutes(30));

        return $empty;
    }

    public function fillVisitLog(int $logId, ?string $ip = null, ?string $countryHint = null, bool $force = false): void
    {
        $log = WebsiteVisitLog::query()->find($logId);
        if (! $log) {
            return;
        }

        if (! $force && (! empty($log->city) || (! empty($log->location_label) && ! empty($log->region)))) {
            return;
        }

        $geo = $this->lookup($ip ?: $log->ip_address, $countryHint);
        if (empty($geo['location_label']) && empty($geo['country']) && empty($geo['city'])) {
            return;
        }

        $log->fill($geo);
        $log->save();
    }

    public function backfillMissing(int $limit = 50, bool $forceRefresh = false): int
    {
        $query = WebsiteVisitLog::query()
            ->whereNotNull('ip_address')
            ->orderByDesc('id')
            ->limit($limit);

        if (! $forceRefresh) {
            $query->where(function ($q) {
                $q->whereNull('location_label')
                    ->orWhere('location_label', '')
                    ->orWhereNull('city')
                    ->orWhere('city', '');
            });
        }

        $logs = $query->get(['id', 'ip_address']);
        $count = 0;

        foreach ($logs as $log) {
            $ip = trim((string) $log->ip_address);
            if ($forceRefresh && $ip !== '') {
                Cache::forget(self::CACHE_PREFIX.md5($ip));
            }

            $hadCache = $ip !== '' && Cache::has(self::CACHE_PREFIX.md5($ip));
            $this->fillVisitLog((int) $log->id, $ip, null, $forceRefresh);
            $count++;

            if (! $hadCache) {
                usleep(150000);
            }
        }

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookupFromProviders(string $ip): array
    {
        $providers = [
            fn () => $this->fromIpWhoIs($ip),
            fn () => $this->fromIpApi($ip),
        ];

        foreach ($providers as $provider) {
            try {
                $result = $provider();
                if (! empty($result['location_label']) || ! empty($result['city']) || ! empty($result['country'])) {
                    return $result;
                }
            } catch (\Throwable $e) {
                Log::warning('IP geolocation provider failed', [
                    'ip' => $ip,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->emptyResult();
    }

    /**
     * @return array<string, mixed>
     */
    protected function fromIpWhoIs(string $ip): array
    {
        $response = Http::timeout(4)
            ->acceptJson()
            ->get('https://ipwho.is/'.$ip);

        if (! $response->successful() || ! $response->json('success')) {
            return $this->emptyResult();
        }

        $country = $response->json('country');
        $countryCode = $response->json('country_code');
        $region = $response->json('region');
        $city = $response->json('city');
        $lat = $response->json('latitude');
        $lon = $response->json('longitude');

        return [
            'country' => $country ? Str::limit((string) $country, 120, '') : null,
            'country_code' => $countryCode ? Str::upper(Str::limit((string) $countryCode, 8, '')) : null,
            'region' => $region ? Str::limit((string) $region, 120, '') : null,
            'city' => $city ? Str::limit((string) $city, 120, '') : null,
            'latitude' => is_numeric($lat) ? (float) $lat : null,
            'longitude' => is_numeric($lon) ? (float) $lon : null,
            'location_label' => $this->buildLabel($city, $region, $country),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function fromIpApi(string $ip): array
    {
        $response = Http::timeout(4)
            ->acceptJson()
            ->get('http://ip-api.com/json/'.$ip, [
                'fields' => 'status,message,country,countryCode,regionName,city,lat,lon',
            ]);

        if (! $response->successful() || $response->json('status') !== 'success') {
            return $this->emptyResult();
        }

        $country = $response->json('country');
        $countryCode = $response->json('countryCode');
        $region = $response->json('regionName');
        $city = $response->json('city');
        $lat = $response->json('lat');
        $lon = $response->json('lon');

        return [
            'country' => $country ? Str::limit((string) $country, 120, '') : null,
            'country_code' => $countryCode ? Str::upper(Str::limit((string) $countryCode, 8, '')) : null,
            'region' => $region ? Str::limit((string) $region, 120, '') : null,
            'city' => $city ? Str::limit((string) $city, 120, '') : null,
            'latitude' => is_numeric($lat) ? (float) $lat : null,
            'longitude' => is_numeric($lon) ? (float) $lon : null,
            'location_label' => $this->buildLabel($city, $region, $country),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyResult(): array
    {
        return [
            'country' => null,
            'country_code' => null,
            'region' => null,
            'city' => null,
            'latitude' => null,
            'longitude' => null,
            'location_label' => null,
        ];
    }

    protected function buildLabel(?string $city, ?string $region, ?string $country): ?string
    {
        $parts = array_values(array_filter([
            $city ? trim((string) $city) : null,
            $region && $region !== $city ? trim((string) $region) : null,
            $country ? trim((string) $country) : null,
        ]));

        if ($parts === []) {
            return null;
        }

        return Str::limit(implode(', ', $parts), 255, '');
    }

    protected function isPrivateIp(string $ip): bool
    {
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
