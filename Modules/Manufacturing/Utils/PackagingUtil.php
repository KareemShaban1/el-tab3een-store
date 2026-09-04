<?php

namespace Modules\Manufacturing\Utils;

use App\Models\Variation;
use Modules\Manufacturing\Entities\MfgPackagingProfile;
use Modules\Manufacturing\Support\PackagingFeature;

class PackagingUtil extends ManufacturingUtil
{
    /**
     * Calculate packaging production quantities from profile and container count.
     */
    public function calculatePackaging(MfgPackagingProfile $profile, $containers_count)
    {
        $containers_count = (float) $containers_count;
        $units_per_carton = (int) $profile->units_per_carton;
        $waste_percent = (float) ($profile->waste_percent ?? 0);

        $full_cartons = $units_per_carton > 0 ? (int) floor($containers_count / $units_per_carton) : 0;
        $leftover_containers = $units_per_carton > 0 ? (int) ($containers_count % $units_per_carton) : 0;

        $bulk_consumed = $containers_count * (float) $profile->bulk_qty_per_container;
        if ($waste_percent > 0) {
            $bulk_consumed = $bulk_consumed * (1 + ($waste_percent / 100));
        }

        $materials = [];
        $profile->load(['materials.variation.product', 'materials.variation.product_variation', 'materials.subUnit']);

        foreach ($profile->materials as $material) {
            $qty = 0;
            if (! empty($material->quantity_per_container)) {
                $qty += $containers_count * (float) $material->quantity_per_container;
            }
            if (! empty($material->quantity_per_carton)) {
                $qty += $full_cartons * (float) $material->quantity_per_carton;
            }

            $variation = $material->variation;
            $materials[] = [
                'id' => $material->id,
                'variation_id' => $material->variation_id,
                'product_id' => $variation->product_id,
                'full_name' => $variation->full_name ?? MfgPackagingProfile::variationLabel($variation),
                'material_role' => $material->material_role,
                'quantity' => $qty,
                'sub_unit_id' => $material->sub_unit_id,
                'enable_stock' => $variation->product->enable_stock ?? 1,
                'unit_price' => $variation->dpp_inc_tax,
            ];
        }

        return [
            'containers_count' => $containers_count,
            'full_cartons' => $full_cartons,
            'leftover_containers' => $leftover_containers,
            'bulk_consumed' => $bulk_consumed,
            'bulk_variation_id' => $profile->bulk_variation_id,
            'output_variation_id' => $profile->output_variation_id,
            'container_type' => $profile->container_type,
            'units_per_carton' => $units_per_carton,
            'materials' => $materials,
        ];
    }

    public function validatePackagingInput(MfgPackagingProfile $profile, $containers_count)
    {
        $containers_count = (float) $containers_count;
        $errors = [];

        if ($containers_count <= 0) {
            $errors[] = __('manufacturing::lang.containers_must_be_greater_than_zero');
        }

        $policy = PackagingFeature::partialCartonPolicy();
        $units_per_carton = (int) $profile->units_per_carton;

        if ($policy === 'strict' && $units_per_carton > 0 && ((int) $containers_count % $units_per_carton) !== 0) {
            $errors[] = __('manufacturing::lang.containers_must_be_multiple_of_carton', ['units' => $units_per_carton]);
        }

        $calc = $this->calculatePackaging($profile, $containers_count);
        if ($calc['full_cartons'] <= 0) {
            $errors[] = __('manufacturing::lang.at_least_one_carton_required');
        }

        return $errors;
    }

    public function getAvailableStock($variation_id, $location_id)
    {
        $variation = Variation::with(['variation_location_details' => function ($q) use ($location_id) {
            $q->where('location_id', $location_id);
        }])->find($variation_id);

        if (empty($variation)) {
            return 0;
        }

        $vld = $variation->variation_location_details->first();

        return ! empty($vld) ? (float) $vld->qty_available : 0;
    }

    public function checkStockAvailability(MfgPackagingProfile $profile, $location_id, $containers_count)
    {
        $calc = $this->calculatePackaging($profile, $containers_count);
        $shortages = [];

        $bulk_available = $this->getAvailableStock($calc['bulk_variation_id'], $location_id);
        if ($bulk_available < $calc['bulk_consumed']) {
            $shortages[] = [
                'name' => __('manufacturing::lang.bulk_sauce'),
                'required' => $calc['bulk_consumed'],
                'available' => $bulk_available,
            ];
        }

        foreach ($calc['materials'] as $material) {
            if (empty($material['enable_stock'])) {
                continue;
            }
            $available = $this->getAvailableStock($material['variation_id'], $location_id);
            if ($available < $material['quantity']) {
                $shortages[] = [
                    'name' => $material['full_name'],
                    'required' => $material['quantity'],
                    'available' => $available,
                ];
            }
        }

        return $shortages;
    }

    public function getProfileDetailsForLocation(MfgPackagingProfile $profile, $location_id, $containers_count = null)
    {
        $profile->load([
            'bulkVariation.product.unit',
            'outputVariation.product.unit',
            'materials.variation.product.unit',
        ]);

        $bulk_stock = $this->getAvailableStock($profile->bulk_variation_id, $location_id);
        $output_stock = $this->getAvailableStock($profile->output_variation_id, $location_id);

        $calc = null;
        if (! is_null($containers_count) && $containers_count !== '') {
            $calc = $this->calculatePackaging($profile, $containers_count);
            foreach ($calc['materials'] as &$material) {
                $material['available'] = $this->getAvailableStock($material['variation_id'], $location_id);
            }
            unset($material);
        }

        return [
            'profile' => $profile,
            'bulk_stock' => $bulk_stock,
            'output_stock' => $output_stock,
            'bulk_label' => MfgPackagingProfile::variationLabel($profile->bulkVariation),
            'output_label' => MfgPackagingProfile::variationLabel($profile->outputVariation),
            'calculation' => $calc,
        ];
    }
}
