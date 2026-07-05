<?php

namespace App\Utils;

use App\LocationsFees\Area;
use App\LocationsFees\City;
use App\LocationsFees\Governorate;

class LocationsFeesUtil
{
    /**
     * @return array{fee: float, governorate_name: string, city_name: string, area_name: string|null, fee_source: string}
     */
    public function resolveDeliveryFee(int $business_id, int $governorate_id, int $city_id, ?int $area_id = null, ?string $custom_area = null): array
    {
        $governorate = Governorate::forBusiness($business_id)->active()->find($governorate_id);
        if (empty($governorate)) {
            throw new \InvalidArgumentException(__('locations_fees.invalid_governorate'));
        }

        $city = City::forBusiness($business_id)
            ->active()
            ->where('governorate_id', $governorate_id)
            ->find($city_id);

        if (empty($city)) {
            throw new \InvalidArgumentException(__('locations_fees.invalid_city'));
        }

        $custom_area = trim((string) $custom_area);
        $area_name = null;
        $fee = (float) $city->delivery_cost;
        $fee_source = 'city';

        if ($area_id) {
            $area = Area::forBusiness($business_id)
                ->active()
                ->where('city_id', $city_id)
                ->find($area_id);

            if (empty($area)) {
                throw new \InvalidArgumentException(__('locations_fees.invalid_area'));
            }

            $area_name = $area->name;
            $fee = (float) $area->delivery_cost;
            $fee_source = 'area';
        } elseif ($custom_area !== '') {
            $area_name = $custom_area;
            $fee_source = 'city';
        }

        return [
            'fee' => $fee,
            'governorate_name' => $governorate->name,
            'city_name' => $city->name,
            'area_name' => $area_name,
            'fee_source' => $fee_source,
        ];
    }

    public function governoratesForDropdown(int $business_id)
    {
        return Governorate::forBusiness($business_id)
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function citiesForDropdown(int $business_id, int $governorate_id)
    {
        return City::forBusiness($business_id)
            ->active()
            ->where('governorate_id', $governorate_id)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function areasForDropdown(int $business_id, int $city_id)
    {
        return Area::forBusiness($business_id)
            ->active()
            ->where('city_id', $city_id)
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
