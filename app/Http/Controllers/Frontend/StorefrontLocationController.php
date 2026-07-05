<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\LocationsFees\Area;
use App\LocationsFees\City;
use App\LocationsFees\Governorate;
use App\Utils\LocationsFeesUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontLocationController extends Controller
{
    public function __construct(private LocationsFeesUtil $locationsFeesUtil) {}

    private function businessId(): int
    {
        return (int) auth('customer')->user()->business_id;
    }

    public function governorates(): JsonResponse
    {
        $business_id = $this->businessId();

        $items = Governorate::forBusiness($business_id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['data' => $items]);
    }

    public function cities(Request $request): JsonResponse
    {
        $business_id = $this->businessId();
        $governorate_id = $request->integer('governorate_id');

        $items = City::forBusiness($business_id)
            ->active()
            ->where('governorate_id', $governorate_id)
            ->orderBy('name')
            ->get(['id', 'name', 'delivery_cost']);

        return response()->json(['data' => $items]);
    }

    public function areas(Request $request): JsonResponse
    {
        $business_id = $this->businessId();
        $city_id = $request->integer('city_id');

        $items = Area::forBusiness($business_id)
            ->active()
            ->where('city_id', $city_id)
            ->orderBy('name')
            ->get(['id', 'name', 'delivery_cost']);

        return response()->json(['data' => $items]);
    }

    public function fee(Request $request): JsonResponse
    {
        $business_id = $this->businessId();

        $governorate_id = $request->integer('governorate_id');
        $city_id = $request->integer('city_id');
        $area_id = $request->filled('area_id') ? $request->integer('area_id') : null;
        $custom_area = $request->input('custom_area');

        try {
            $resolved = $this->locationsFeesUtil->resolveDeliveryFee(
                $business_id,
                $governorate_id,
                $city_id,
                $area_id,
                $custom_area
            );

            return response()->json([
                'success' => true,
                'fee' => $resolved['fee'],
                'fee_source' => $resolved['fee_source'],
                'governorate_name' => $resolved['governorate_name'],
                'city_name' => $resolved['city_name'],
                'area_name' => $resolved['area_name'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage(),
            ], 422);
        }
    }
}
