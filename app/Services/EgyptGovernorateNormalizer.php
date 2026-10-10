<?php

namespace App\Services;

use Illuminate\Support\Str;

class EgyptGovernorateNormalizer
{
    /**
     * @var array<string, string>
     */
    protected array $governorates = [
        'cairo' => 'القاهرة',
        'giza' => 'الجيزة',
        'alexandria' => 'الإسكندرية',
        'dakahlia' => 'الدقهلية',
        'red_sea' => 'البحر الأحمر',
        'beheira' => 'البحيرة',
        'fayoum' => 'الفيوم',
        'gharbia' => 'الغربية',
        'ismailia' => 'الإسماعيلية',
        'menofia' => 'المنوفية',
        'minya' => 'المنيا',
        'qalyubia' => 'القليوبية',
        'new_valley' => 'الوادي الجديد',
        'suez' => 'السويس',
        'aswan' => 'أسوان',
        'assiut' => 'أسيوط',
        'beni_suef' => 'بني سويف',
        'port_said' => 'بورسعيد',
        'damietta' => 'دمياط',
        'sharkia' => 'الشرقية',
        'south_sinai' => 'جنوب سيناء',
        'kafr_el_sheikh' => 'كفر الشيخ',
        'matrouh' => 'مطروح',
        'luxor' => 'الأقصر',
        'qena' => 'قنا',
        'north_sinai' => 'شمال سيناء',
        'sohag' => 'سوهاج',
    ];

    /**
     * @var array<string, string>
     */
    protected array $aliases = [
        'cairo' => 'cairo', 'al qahirah' => 'cairo', 'القاهرة' => 'cairo', 'قاهره' => 'cairo', 'cairo governorate' => 'cairo',
        'giza' => 'giza', 'gizah' => 'giza', 'al jizah' => 'giza', 'الجيزة' => 'giza', 'جيزة' => 'giza', 'giza governorate' => 'giza',
        '6th of october' => 'giza', 'sheikh zayed' => 'giza', 'الشيخ زايد' => 'giza',
        'alexandria' => 'alexandria', 'al iskandariyah' => 'alexandria', 'الإسكندرية' => 'alexandria', 'اسكندرية' => 'alexandria',
        'dakahlia' => 'dakahlia', 'ad daqahliyah' => 'dakahlia', 'الدقهلية' => 'dakahlia', 'mansoura' => 'dakahlia', 'المنصورة' => 'dakahlia',
        'red sea' => 'red_sea', 'البحر الأحمر' => 'red_sea', 'hurghada' => 'red_sea', 'الغردقة' => 'red_sea',
        'beheira' => 'beheira', 'al buhayrah' => 'beheira', 'البحيرة' => 'beheira', 'damanhur' => 'beheira',
        'fayoum' => 'fayoum', 'faiyum' => 'fayoum', 'الفيوم' => 'fayoum',
        'gharbia' => 'gharbia', 'al gharbiyah' => 'gharbia', 'الغربية' => 'gharbia', 'tanta' => 'gharbia', 'طنطا' => 'gharbia',
        'ismailia' => 'ismailia', 'الإسماعيلية' => 'ismailia', 'اسماعيلية' => 'ismailia',
        'menofia' => 'menofia', 'monufia' => 'menofia', 'المنوفية' => 'menofia',
        'minya' => 'minya', 'المنيا' => 'minya',
        'qalyubia' => 'qalyubia', 'qalubia' => 'qalyubia', 'qalyubiya' => 'qalyubia', 'al qalyubiyah' => 'qalyubia',
        'القليوبية' => 'qalyubia', 'قليوبية' => 'qalyubia',
        'banha' => 'qalyubia', 'benha' => 'qalyubia', 'بنها' => 'qalyubia',
        'obour' => 'qalyubia', 'العبور' => 'qalyubia', 'shubra el kheima' => 'qalyubia', 'شبرا الخيمة' => 'qalyubia',
        'khanka' => 'qalyubia', 'الخانكة' => 'qalyubia', 'qalyub' => 'qalyubia', 'قليوب' => 'qalyubia',
        'new valley' => 'new_valley', 'الوادي الجديد' => 'new_valley',
        'suez' => 'suez', 'السويس' => 'suez',
        'aswan' => 'aswan', 'أسوان' => 'aswan',
        'assiut' => 'assiut', 'asyut' => 'assiut', 'أسيوط' => 'assiut',
        'beni suef' => 'beni_suef', 'بني سويف' => 'beni_suef',
        'port said' => 'port_said', 'بورسعيد' => 'port_said', 'بور سعيد' => 'port_said',
        'damietta' => 'damietta', 'دمياط' => 'damietta',
        'sharkia' => 'sharkia', 'sharqia' => 'sharkia', 'الشرقية' => 'sharkia', 'zagazig' => 'sharkia',
        'south sinai' => 'south_sinai', 'جنوب سيناء' => 'south_sinai',
        'kafr el sheikh' => 'kafr_el_sheikh', 'كفر الشيخ' => 'kafr_el_sheikh',
        'fuwwah' => 'kafr_el_sheikh', 'fowa' => 'kafr_el_sheikh', 'فوه' => 'kafr_el_sheikh',
        'matrouh' => 'matrouh', 'مطروح' => 'matrouh',
        'luxor' => 'luxor', 'الأقصر' => 'luxor',
        'qena' => 'qena', 'قنا' => 'qena',
        'north sinai' => 'north_sinai', 'شمال سيناء' => 'north_sinai',
        'sohag' => 'sohag', 'سوهاج' => 'sohag',
    ];

    /**
     * Governorate centers [lat, lon].
     *
     * @var array<string, array{0:float,1:float}>
     */
    protected array $centers = [
        'cairo' => [30.0444, 31.2357],
        'giza' => [30.0131, 31.2089],
        'alexandria' => [31.2001, 29.9187],
        'dakahlia' => [31.0409, 31.3785],
        'red_sea' => [27.2574, 33.8129],
        'beheira' => [31.0341, 30.4682],
        'fayoum' => [29.3084, 30.8428],
        'gharbia' => [30.7865, 31.0004],
        'ismailia' => [30.5965, 32.2715],
        'menofia' => [30.5972, 30.9876],
        'minya' => [28.1099, 30.7503],
        'qalyubia' => [30.4667, 31.1833], // Benha
        'new_valley' => [25.4400, 30.5500],
        'suez' => [29.9668, 32.5498],
        'aswan' => [24.0889, 32.8998],
        'assiut' => [27.1809, 31.1837],
        'beni_suef' => [29.0661, 31.0996],
        'port_said' => [31.2653, 32.3019],
        'damietta' => [31.4165, 31.8133],
        'sharkia' => [30.5877, 31.5020],
        'south_sinai' => [28.2410, 33.6220],
        'kafr_el_sheikh' => [31.1117, 30.9394],
        'matrouh' => [31.3543, 27.2373],
        'luxor' => [25.6872, 32.6396],
        'qena' => [26.1551, 32.7160],
        'north_sinai' => [31.1320, 33.8030],
        'sohag' => [26.5590, 31.6950],
    ];

    /**
     * Known cities for more precise labels: [lat, lon, arabic_name, governorate_slug]
     *
     * @var array<int, array{0:float,1:float,2:string,3:string}>
     */
    protected array $cities = [
        [30.4667, 31.1833, 'بنها', 'qalyubia'],
        [30.1286, 31.2422, 'شبرا الخيمة', 'qalyubia'],
        [30.2280, 31.3530, 'العبور', 'qalyubia'],
        [30.1790, 31.2050, 'قليوب', 'qalyubia'],
        [30.2100, 31.3680, 'الخانكة', 'qalyubia'],
        [30.0444, 31.2357, 'القاهرة', 'cairo'],
        [30.0131, 31.2089, 'الجيزة', 'giza'],
        [31.2089, 30.4410, 'فوه', 'kafr_el_sheikh'],
        [31.1117, 30.9394, 'كفر الشيخ', 'kafr_el_sheikh'],
        [31.0409, 31.3785, 'المنصورة', 'dakahlia'],
        [30.7865, 31.0004, 'طنطا', 'gharbia'],
        [31.2001, 29.9187, 'الإسكندرية', 'alexandria'],
    ];

    /**
     * @param  array<string, mixed>  $geo
     * @return array<string, mixed>
     */
    public function normalize(array $geo, bool $preferCoords = false): array
    {
        $countryCode = strtoupper((string) ($geo['country_code'] ?? ''));
        $country = strtolower((string) ($geo['country'] ?? ''));
        $lat = isset($geo['latitude']) && is_numeric($geo['latitude']) ? (float) $geo['latitude'] : null;
        $lon = isset($geo['longitude']) && is_numeric($geo['longitude']) ? (float) $geo['longitude'] : null;

        $isEgypt = $countryCode === 'EG'
            || str_contains($country, 'egypt')
            || str_contains((string) ($geo['country'] ?? ''), 'مصر')
            || $this->looksLikeEgyptCoords($lat, $lon);

        if (! $isEgypt) {
            return $geo;
        }

        if ($preferCoords && $lat !== null && $lon !== null) {
            return $this->fromCoordinates($lat, $lon);
        }

        $slug = $this->resolveSlugFromText(
            (string) ($geo['region'] ?? ''),
            (string) ($geo['city'] ?? ''),
            (string) ($geo['location_label'] ?? '')
        );

        // For IP data, prefer coordinates over ISP city names (often wrong in Egypt).
        if ($lat !== null && $lon !== null) {
            $coordGeo = $this->fromCoordinates($lat, $lon);
            // If text and coords disagree, trust coords.
            if ($slug === null || ($coordGeo['region'] ?? null) !== ($this->governorates[$slug] ?? null)) {
                return $coordGeo;
            }
        }

        if ($slug === null) {
            $geo['country'] = 'مصر';
            $geo['country_code'] = 'EG';
            $geo['location_label'] = $this->buildLabel($geo['city'] ?? null, $geo['region'] ?? null, 'مصر');

            return $geo;
        }

        return $this->compose($slug, $geo['city'] ?? null, $lat, $lon);
    }

    /**
     * Build location from precise browser / GPS coordinates.
     *
     * @return array<string, mixed>
     */
    public function fromCoordinates(float $lat, float $lon): array
    {
        $cityMatch = $this->nearestCity($lat, $lon, 25);
        if ($cityMatch !== null) {
            return $this->compose($cityMatch['slug'], $cityMatch['city'], $lat, $lon);
        }

        $slug = $this->nearestGovernorate($lat, $lon, 120);
        if ($slug === null) {
            return [
                'country' => 'مصر',
                'country_code' => 'EG',
                'region' => null,
                'city' => null,
                'latitude' => $lat,
                'longitude' => $lon,
                'location_label' => 'مصر',
            ];
        }

        return $this->compose($slug, null, $lat, $lon);
    }

    /**
     * @return array<string, mixed>
     */
    protected function compose(string $slug, ?string $city, ?float $lat, ?float $lon): array
    {
        $governorate = $this->governorates[$slug];
        $cityLabel = null;

        if ($city) {
            $cityKey = $this->normalizeKey($city);
            $cityIsGov = isset($this->aliases[$cityKey]) && $this->aliases[$cityKey] === $slug
                && ($this->governorates[$slug] === $city || $this->normalizeKey($governorate) === $cityKey);
            $cityLabel = $cityIsGov ? null : trim($city);

            // Prefer Arabic city label for known aliases.
            if (in_array($cityKey, ['banha', 'benha', 'بنها'], true)) {
                $cityLabel = 'بنها';
            }
        }

        return [
            'country' => 'مصر',
            'country_code' => 'EG',
            'region' => $governorate,
            'city' => $cityLabel ?: $governorate,
            'latitude' => $lat,
            'longitude' => $lon,
            'location_label' => $this->buildLabel($cityLabel ?: $governorate, $governorate, 'مصر'),
        ];
    }

    protected function resolveSlugFromText(string $region, string $city, string $label): ?string
    {
        foreach ([$city, $region, $label] as $value) {
            $slug = $this->matchAlias($value);
            if ($slug !== null) {
                return $slug;
            }
        }

        $combined = $this->normalizeKey($city.' '.$region.' '.$label);
        foreach ($this->aliases as $alias => $slug) {
            if ($alias !== '' && str_contains($combined, $alias)) {
                return $slug;
            }
        }

        return null;
    }

    protected function matchAlias(string $value): ?string
    {
        $key = $this->normalizeKey($value);
        if ($key === '') {
            return null;
        }

        if (isset($this->aliases[$key])) {
            return $this->aliases[$key];
        }

        $key = preg_replace('/^(muhafazat|muḩafazat|governorate of)\s+/u', '', $key) ?: $key;
        $key = preg_replace('/\s+governorate$/u', '', $key) ?: $key;

        return $this->aliases[$key] ?? null;
    }

    /**
     * @return array{slug:string,city:string}|null
     */
    protected function nearestCity(float $lat, float $lon, float $maxKm): ?array
    {
        $best = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($this->cities as [$cLat, $cLon, $name, $slug]) {
            $distance = $this->distanceKm($lat, $lon, $cLat, $cLon);
            if ($distance < $bestDistance && $distance <= $maxKm) {
                $bestDistance = $distance;
                $best = ['slug' => $slug, 'city' => $name];
            }
        }

        return $best;
    }

    protected function nearestGovernorate(float $lat, float $lon, float $maxKm): ?string
    {
        $best = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($this->centers as $slug => [$cLat, $cLon]) {
            $distance = $this->distanceKm($lat, $lon, $cLat, $cLon);
            if ($distance < $bestDistance && $distance <= $maxKm) {
                $bestDistance = $distance;
                $best = $slug;
            }
        }

        return $best;
    }

    protected function looksLikeEgyptCoords(?float $lat, ?float $lon): bool
    {
        if ($lat === null || $lon === null) {
            return false;
        }

        return $lat >= 22.0 && $lat <= 32.0 && $lon >= 24.5 && $lon <= 37.0;
    }

    protected function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }

    protected function normalizeKey(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replace(['ـ', '_', '-', '/', '\\', ',', '(', ')'], ' ')
            ->replace(['ḩ', 'ḥ'], 'h')
            ->replace(['`', '’', "'"], '')
            ->squish()
            ->toString();
    }

    protected function buildLabel(?string $city, ?string $region, ?string $country): ?string
    {
        $parts = array_values(array_filter([
            $city && $city !== $region ? trim((string) $city) : null,
            $region ? trim((string) $region) : null,
            $country ? trim((string) $country) : null,
        ]));

        return $parts === [] ? null : Str::limit(implode(', ', $parts), 255, '');
    }
}
