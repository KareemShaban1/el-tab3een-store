<?php

namespace App\Services;

use App\WebsiteVisitLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IpGeolocationService
{
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
        if ($ip === '' || $this->isPrivateIp($ip)) {
            return array_merge($empty, [
                'country' => __('website_logs.local_network'),
                'location_label' => __('website_logs.local_network'),
            ]);
        }

        $cacheKey = 'ip_geo_v1:'.md5($ip);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($ip, $countryHint, $empty) {
            try {
                $response = Http::timeout(3)
                    ->acceptJson()
                    ->get('http://ip-api.com/json/'.$ip, [
                        'fields' => 'status,message,country,countryCode,regionName,city,lat,lon',
                    ]);

                if ($response->successful() && ($response->json('status') === 'success')) {
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
        });
    }

    public function fillVisitLog(int $logId, ?string $ip = null, ?string $countryHint = null): void
    {
        $log = WebsiteVisitLog::query()->find($logId);
        if (! $log) {
            return;
        }

        // Skip only when we already have a detailed location (city/region), not just a country code hint.
        if (! empty($log->city) || (! empty($log->location_label) && ! empty($log->region))) {
            return;
        }

        $geo = $this->lookup($ip ?: $log->ip_address, $countryHint);
        if (empty($geo['location_label']) && empty($geo['country']) && empty($geo['city'])) {
            return;
        }

        $log->fill($geo);
        $log->save();
    }

    public function backfillMissing(int $limit = 50): int
    {
        $logs = WebsiteVisitLog::query()
            ->where(function ($q) {
                $q->whereNull('location_label')
                    ->orWhere('location_label', '');
            })
            ->whereNotNull('ip_address')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'ip_address']);

        $count = 0;
        foreach ($logs as $log) {
            $ip = trim((string) $log->ip_address);
            $hadCache = $ip !== '' && Cache::has('ip_geo_v1:'.md5($ip));
            $this->fillVisitLog((int) $log->id, $ip);
            $count++;
            // Stay under free API rate limits when cache misses.
            if (! $hadCache) {
                usleep(200000);
            }
        }

        return $count;
    }

    protected function buildLabel(?string $city, ?string $region, ?string $country): ?string
    {
        $parts = array_values(array_filter([
            $city ? trim($city) : null,
            $region && $region !== $city ? trim($region) : null,
            $country ? trim($country) : null,
        ]));

        if ($parts === []) {
            return null;
        }

        return Str::limit(implode(', ', $parts), 255, '');
    }

    protected function isPrivateIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
