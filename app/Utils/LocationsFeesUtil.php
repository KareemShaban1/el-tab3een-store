<?php

namespace App\Utils;

use App\Contact;
use App\LocationsFees\Area;
use App\LocationsFees\City;
use App\LocationsFees\Governorate;
use App\Transaction;

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
        // Default to city fee; use area fee only when a valid area is selected.
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

    /**
     * Resolve a customer's previous delivery location (last order, then contact city/state names).
     *
     * @return array{
     *     lf_governorate_id: int,
     *     lf_city_id: int,
     *     lf_area_id: int|null,
     *     lf_custom_area: string|null,
     *     fee: float,
     *     fee_source: string,
     *     governorate_name: string,
     *     city_name: string,
     *     area_name: string|null
     * }|null
     */
    public function resolvePreviousCustomerLocation(int $business_id, Contact $customer): ?array
    {
        $location = $this->locationFromLastOrder($business_id, (int) $customer->id)
            ?? $this->locationFromContactNames($business_id, $customer);

        if (empty($location)) {
            return null;
        }

        try {
            $resolved = $this->resolveDeliveryFee(
                $business_id,
                (int) $location['lf_governorate_id'],
                (int) $location['lf_city_id'],
                ! empty($location['lf_area_id']) ? (int) $location['lf_area_id'] : null,
                $location['lf_custom_area'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        return [
            'lf_governorate_id' => (int) $location['lf_governorate_id'],
            'lf_city_id' => (int) $location['lf_city_id'],
            'lf_area_id' => ! empty($location['lf_area_id']) ? (int) $location['lf_area_id'] : null,
            'lf_custom_area' => $location['lf_custom_area'] ?? null,
            'fee' => (float) $resolved['fee'],
            'fee_source' => $resolved['fee_source'],
            'governorate_name' => $resolved['governorate_name'],
            'city_name' => $resolved['city_name'],
            'area_name' => $resolved['area_name'],
        ];
    }

    /**
     * @return array{lf_governorate_id: int, lf_city_id: int, lf_area_id: int|null, lf_custom_area: string|null}|null
     */
    private function locationFromLastOrder(int $business_id, int $contact_id): ?array
    {
        $order = Transaction::where('business_id', $business_id)
            ->where('contact_id', $contact_id)
            ->where('type', 'sell')
            ->where('source', 'ecommerce')
            ->whereNotNull('order_addresses')
            ->orderByDesc('id')
            ->first(['order_addresses']);

        if (empty($order?->order_addresses)) {
            return null;
        }

        $decoded = is_array($order->order_addresses)
            ? $order->order_addresses
            : json_decode((string) $order->order_addresses, true);

        $shipping = $decoded['shipping_address'] ?? null;
        if (! is_array($shipping)) {
            return null;
        }

        $governorate_id = (int) ($shipping['lf_governorate_id'] ?? 0);
        $city_id = (int) ($shipping['lf_city_id'] ?? 0);
        if ($governorate_id <= 0 || $city_id <= 0) {
            return null;
        }

        return [
            'lf_governorate_id' => $governorate_id,
            'lf_city_id' => $city_id,
            'lf_area_id' => ! empty($shipping['lf_area_id']) ? (int) $shipping['lf_area_id'] : null,
            'lf_custom_area' => trim((string) ($shipping['lf_custom_area'] ?? '')) ?: null,
        ];
    }

    /**
     * @return array{lf_governorate_id: int, lf_city_id: int, lf_area_id: int|null, lf_custom_area: string|null}|null
     */
    private function locationFromContactNames(int $business_id, Contact $customer): ?array
    {
        $state = trim((string) ($customer->state ?? ''));
        $city_name = trim((string) ($customer->city ?? ''));
        if ($state === '' || $city_name === '') {
            return null;
        }

        $governorate = Governorate::forBusiness($business_id)
            ->active()
            ->where('name', $state)
            ->first();

        if (empty($governorate)) {
            return null;
        }

        $city = City::forBusiness($business_id)
            ->active()
            ->where('governorate_id', $governorate->id)
            ->where('name', $city_name)
            ->first();

        if (empty($city)) {
            return null;
        }

        return [
            'lf_governorate_id' => (int) $governorate->id,
            'lf_city_id' => (int) $city->id,
            'lf_area_id' => null,
            'lf_custom_area' => null,
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
