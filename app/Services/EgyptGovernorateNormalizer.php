<?php

namespace App\Services;

use Illuminate\Support\Str;

class EgyptGovernorateNormalizer
{
    /**
     * Official Arabic governorate names keyed by slug.
     *
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
     * Aliases (english / arabic / transliteration) => slug.
     *
     * @var array<string, string>
     */
    protected array $aliases = [
        // Cairo
        'cairo' => 'cairo', 'al qahirah' => 'cairo', 'al-qahirah' => 'cairo', 'القاهرة' => 'cairo', 'قاهره' => 'cairo',
        'muḩafazat al qahirah' => 'cairo', 'muhafazat al qahirah' => 'cairo', 'cairo governorate' => 'cairo',
        // Giza
        'giza' => 'giza', 'gizah' => 'giza', 'al jizah' => 'giza', 'al-jizah' => 'giza', 'الجيزة' => 'giza', 'جيزة' => 'giza',
        'muḩafazat al jizah' => 'giza', 'muhafazat al jizah' => 'giza', 'giza governorate' => 'giza',
        '6th of october' => 'giza', 'october' => 'giza', 'sheikh zayed' => 'giza', 'الشيخ زايد' => 'giza', 'أكتوبر' => 'giza',
        'hadayek october' => 'giza', 'dokki' => 'giza', 'mohandessin' => 'giza', 'haram' => 'giza',
        // Alexandria
        'alexandria' => 'alexandria', 'al iskandariyah' => 'alexandria', 'الإسكندرية' => 'alexandria', 'اسكندرية' => 'alexandria',
        'muḩafazat al iskandariyah' => 'alexandria', 'alexandria governorate' => 'alexandria',
        // Dakahlia
        'dakahlia' => 'dakahlia', 'ad daqahliyah' => 'dakahlia', 'الدقهلية' => 'dakahlia', 'mansoura' => 'dakahlia', 'المنصورة' => 'dakahlia',
        // Red Sea
        'red sea' => 'red_sea', 'al bahr al ahmar' => 'red_sea', 'البحر الأحمر' => 'red_sea', 'hurghada' => 'red_sea', 'الغردقة' => 'red_sea',
        // Beheira
        'beheira' => 'beheira', 'al buhayrah' => 'beheira', 'البحيرة' => 'beheira', 'damanhur' => 'beheira',
        // Fayoum
        'fayoum' => 'fayoum', 'faiyum' => 'fayoum', 'al fayyum' => 'fayoum', 'الفيوم' => 'fayoum',
        // Gharbia
        'gharbia' => 'gharbia', 'al gharbiyah' => 'gharbia', 'الغربية' => 'gharbia', 'tanta' => 'gharbia', 'طنطا' => 'gharbia',
        // Ismailia
        'ismailia' => 'ismailia', 'al ismailiyah' => 'ismailia', 'الإسماعيلية' => 'ismailia', 'اسماعيلية' => 'ismailia',
        // Menofia
        'menofia' => 'menofia', 'monufia' => 'menofia', 'al minufiyah' => 'menofia', 'المنوفية' => 'menofia', 'shibin el kom' => 'menofia',
        // Minya
        'minya' => 'minya', 'al minya' => 'minya', 'المنيا' => 'minya',
        // Qalyubia
        'qalyubia' => 'qalyubia', 'al qalyubiyah' => 'qalyubia', 'القليوبية' => 'qalyubia', 'banha' => 'qalyubia', 'obour' => 'qalyubia',
        'shubra el kheima' => 'qalyubia', 'شبرا الخيمة' => 'qalyubia',
        // New Valley
        'new valley' => 'new_valley', 'al wadi al jadid' => 'new_valley', 'الوادي الجديد' => 'new_valley',
        // Suez
        'suez' => 'suez', 'as suways' => 'suez', 'السويس' => 'suez',
        // Aswan
        'aswan' => 'aswan', 'أسوان' => 'aswan',
        // Assiut
        'assiut' => 'assiut', 'asyut' => 'assiut', 'أسيوط' => 'assiut',
        // Beni Suef
        'beni suef' => 'beni_suef', 'bani suwayf' => 'beni_suef', 'بني سويف' => 'beni_suef',
        // Port Said
        'port said' => 'port_said', 'bur sa`id' => 'port_said', 'بورسعيد' => 'port_said', 'بور سعيد' => 'port_said',
        // Damietta
        'damietta' => 'damietta', 'dumyat' => 'damietta', 'دمياط' => 'damietta',
        // Sharkia
        'sharkia' => 'sharkia', 'sharqia' => 'sharkia', 'ash sharqiyah' => 'sharkia', 'الشرقية' => 'sharkia', 'zagazig' => 'sharkia',
        // South Sinai
        'south sinai' => 'south_sinai', 'janub sina' => 'south_sinai', 'جنوب سيناء' => 'south_sinai', 'sharm el sheikh' => 'south_sinai',
        // Kafr El Sheikh
        'kafr el sheikh' => 'kafr_el_sheikh', 'kafr ash shaykh' => 'kafr_el_sheikh', 'كفر الشيخ' => 'kafr_el_sheikh',
        // Matrouh
        'matrouh' => 'matrouh', 'matruh' => 'matrouh', 'مطروح' => 'matrouh', 'marsa matruh' => 'matrouh',
        // Luxor
        'luxor' => 'luxor', 'al uqsur' => 'luxor', 'الأقصر' => 'luxor', 'الاقصر' => 'luxor',
        // Qena
        'qena' => 'qena', 'qina' => 'qena', 'قنا' => 'qena',
        // North Sinai
        'north sinai' => 'north_sinai', 'shamal sina' => 'north_sinai', 'شمال سيناء' => 'north_sinai', 'arish' => 'north_sinai',
        // Sohag
        'sohag' => 'sohag', 'sawhaj' => 'sohag', 'سوهاج' => 'sohag',
    ];

    /**
     * Approximate lat/lon boxes for Egypt governorates (fallback).
     *
     * @var array<string, array{0:float,1:float,2:float,3:float}>
     */
    protected array $boxes = [
        // [minLat, maxLat, minLon, maxLon]
        'alexandria' => [30.7, 31.4, 29.4, 30.2],
        'beheira' => [30.3, 31.6, 29.6, 31.0],
        'matrouh' => [28.0, 31.7, 24.7, 29.6],
        'kafr_el_sheikh' => [30.9, 31.7, 30.5, 31.5],
        'gharbia' => [30.6, 31.2, 30.6, 31.3],
        'menofia' => [30.3, 30.9, 30.6, 31.3],
        'dakahlia' => [30.8, 31.6, 31.1, 32.2],
        'damietta' => [31.2, 31.6, 31.5, 32.1],
        'port_said' => [31.0, 31.4, 32.1, 32.5],
        'sharkia' => [30.2, 31.2, 31.2, 32.3],
        'ismailia' => [30.2, 31.2, 32.0, 32.8],
        'suez' => [29.5, 30.3, 32.2, 33.0],
        'north_sinai' => [30.0, 31.4, 32.5, 34.9],
        'south_sinai' => [27.6, 29.8, 32.8, 34.9],
        'qalyubia' => [30.1, 30.6, 31.0, 31.5],
        'cairo' => [29.9, 30.2, 31.1, 31.5],
        'giza' => [29.5, 30.3, 30.6, 31.4],
        'fayoum' => [29.0, 29.7, 30.3, 31.2],
        'beni_suef' => [28.6, 29.4, 30.5, 31.5],
        'minya' => [27.5, 28.8, 30.4, 31.5],
        'assiut' => [26.8, 27.6, 30.7, 31.7],
        'sohag' => [26.1, 27.0, 31.2, 32.2],
        'qena' => [25.5, 26.5, 32.2, 33.2],
        'luxor' => [25.4, 26.0, 32.4, 32.9],
        'aswan' => [22.0, 25.4, 31.5, 33.5],
        'red_sea' => [22.0, 27.5, 32.5, 37.0],
        'new_valley' => [22.0, 27.8, 25.0, 31.0],
    ];

    /**
     * Normalize Egypt location fields to a proper governorate label.
     *
     * @param  array<string, mixed>  $geo
     * @return array<string, mixed>
     */
    public function normalize(array $geo): array
    {
        $countryCode = strtoupper((string) ($geo['country_code'] ?? ''));
        $country = strtolower((string) ($geo['country'] ?? ''));
        $isEgypt = $countryCode === 'EG' || str_contains($country, 'egypt') || str_contains((string) ($geo['country'] ?? ''), 'مصر');

        if (! $isEgypt) {
            return $geo;
        }

        $geo['country'] = 'مصر';
        $geo['country_code'] = 'EG';

        $slug = $this->resolveSlug(
            (string) ($geo['region'] ?? ''),
            (string) ($geo['city'] ?? ''),
            (string) ($geo['location_label'] ?? ''),
            isset($geo['latitude']) ? (float) $geo['latitude'] : null,
            isset($geo['longitude']) ? (float) $geo['longitude'] : null
        );

        if ($slug === null) {
            // Keep provider data but force Egypt country label.
            $geo['location_label'] = $this->buildLabel(
                $geo['city'] ?? null,
                $geo['region'] ?? null,
                'مصر'
            );

            return $geo;
        }

        $governorateAr = $this->governorates[$slug];
        $geo['region'] = $governorateAr;
        // Prefer keeping city if useful and different.
        if (! empty($geo['city'])) {
            $cityNorm = $this->normalizeKey((string) $geo['city']);
            if (isset($this->aliases[$cityNorm]) && $this->aliases[$cityNorm] === $slug) {
                // City is just another name for the governorate.
                $geo['city'] = $governorateAr;
            }
        } else {
            $geo['city'] = $governorateAr;
        }

        $geo['location_label'] = $this->buildLabel($geo['city'], $governorateAr, 'مصر');

        return $geo;
    }

    protected function resolveSlug(string $region, string $city, string $label, ?float $lat, ?float $lon): ?string
    {
        foreach ([$region, $city, $label] as $value) {
            $slug = $this->matchAlias($value);
            if ($slug !== null) {
                return $slug;
            }
        }

        // Try matching parts of composite labels.
        $combined = $this->normalizeKey($region.' '.$city.' '.$label);
        foreach ($this->aliases as $alias => $slug) {
            if ($alias !== '' && str_contains($combined, $alias)) {
                return $slug;
            }
        }

        if ($lat !== null && $lon !== null) {
            return $this->matchByCoordinates($lat, $lon);
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

        // Strip common prefixes like "muhafazat", "governorate of"
        $key = preg_replace('/^(muhafazat|muḩafazat|governorate of|govemorate of)\s+/u', '', $key) ?: $key;
        $key = preg_replace('/\s+governorate$/u', '', $key) ?: $key;

        return $this->aliases[$key] ?? null;
    }

    protected function matchByCoordinates(float $lat, float $lon): ?string
    {
        foreach ($this->boxes as $slug => [$minLat, $maxLat, $minLon, $maxLon]) {
            if ($lat >= $minLat && $lat <= $maxLat && $lon >= $minLon && $lon <= $maxLon) {
                return $slug;
            }
        }

        return null;
    }

    protected function normalizeKey(string $value): string
    {
        $value = Str::of($value)
            ->lower()
            ->replace(['ـ', '_', '-', '/', '\\', ',', '(', ')'], ' ')
            ->replace(['á', 'à', 'ă', 'ã', 'â', 'ä'], 'a')
            ->replace(['ḩ', 'ḥ', 'ḩ'], 'h')
            ->replace(['ş', 'ș', 'š'], 's')
            ->replace(['ţ', 'ț'], 't')
            ->replace(['`', '’', "'"], '')
            ->squish()
            ->toString();

        // Collapse arabic alef variants etc is already handled via aliases.
        return trim($value);
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
