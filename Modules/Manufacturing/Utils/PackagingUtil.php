<?php

namespace Modules\Manufacturing\Utils;

use App\Variation;
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
        $uses_carton = $profile->usesCarton();
        $units_per_carton = $uses_carton ? (int) $profile->units_per_carton : 0;
        $waste_percent = (float) ($profile->waste_percent ?? 0);

        $full_cartons = $units_per_carton > 0 ? (int) floor($containers_count / $units_per_carton) : 0;
        $leftover_containers = $units_per_carton > 0 ? (int) ($containers_count % $units_per_carton) : 0;

        // Carton mode: stock output = full cartons. Container-only: stock output = bottles/bags filled.
        $output_quantity = $uses_carton ? $full_cartons : $containers_count;

        $bulk_consumed = $containers_count * (float) $profile->bulk_qty_per_container;
        if ($waste_percent > 0) {
            $bulk_consumed = $bulk_consumed * (1 + ($waste_percent / 100));
        }

        $materials = [];
        $profile->load(['materials.variation.product', 'materials.variation.product_variation', 'materials.subUnit']);

        foreach ($profile->materials as $material) {
            $qty = $this->materialRequiredQuantity($material, $containers_count, $full_cartons, $uses_carton);

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
            'output_quantity' => $output_quantity,
            'uses_carton' => $uses_carton,
            'bulk_consumed' => $bulk_consumed,
            'bulk_variation_id' => $profile->bulk_variation_id,
            'output_variation_id' => $profile->output_variation_id,
            'container_type' => $profile->container_type,
            'units_per_carton' => $units_per_carton,
            'bulk_qty_per_container' => (float) $profile->bulk_qty_per_container,
            'materials' => $materials,
        ];
    }

    /**
     * On finalize only: waste (bulk units) is added to bulk deduction.
     * Finished output is never reduced by waste.
     */
    public function applyFinalizeWaste(array $calc, MfgPackagingProfile $profile, $waste_qty, $is_final)
    {
        $waste_qty = max(0, (float) $waste_qty);
        $calc['waste_quantity'] = $waste_qty;
        $calc['bulk_to_deduct'] = (float) $calc['bulk_consumed'];
        $calc['final_output_quantity'] = (float) $calc['output_quantity'];

        if ($is_final && $waste_qty > 0) {
            $calc['bulk_to_deduct'] = (float) $calc['bulk_consumed'] + $waste_qty;
        }

        return $calc;
    }

    /**
     * Bottle/cap/label scale with containers only.
     * Outer carton scales with full cartons only (skipped in container-only profiles).
     * Other roles use whichever qty fields are set (no double-count if both filled: prefer per-container).
     */
    protected function materialRequiredQuantity($material, $containers_count, $full_cartons, $uses_carton = true)
    {
        $role = $material->material_role;
        $per_container = ! empty($material->quantity_per_container) ? (float) $material->quantity_per_container : 0;
        $per_carton = ! empty($material->quantity_per_carton) ? (float) $material->quantity_per_carton : 0;

        if (in_array($role, ['container', 'closure', 'label'], true)) {
            return $containers_count * ($per_container > 0 ? $per_container : 1);
        }

        if ($role === 'outer_carton') {
            if (! $uses_carton) {
                return 0;
            }
            $rate = $per_carton > 0 ? $per_carton : ($per_container > 0 ? $per_container : 1);

            return $full_cartons * $rate;
        }

        // role "other" / unset: use one scale only to avoid double-counting
        if ($per_container > 0) {
            return $containers_count * $per_container;
        }

        if ($uses_carton && $per_carton > 0) {
            return $full_cartons * $per_carton;
        }

        return 0;
    }

    public function validatePackagingInput(MfgPackagingProfile $profile, $containers_count)
    {
        $containers_count = (float) $containers_count;
        $errors = [];

        if ($containers_count <= 0) {
            $errors[] = __('manufacturing::lang.containers_must_be_greater_than_zero');
        }

        // Container-only profiles: any positive container count is valid
        if (! $profile->usesCarton()) {
            return $errors;
        }

        $policy = PackagingFeature::partialCartonPolicy();
        $units_per_carton = (int) $profile->units_per_carton;

        if ($units_per_carton <= 0) {
            $errors[] = __('manufacturing::lang.units_per_carton_required');

            return $errors;
        }

        if ($policy === 'strict' && ((int) $containers_count % $units_per_carton) !== 0) {
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

    public function checkStockAvailability(MfgPackagingProfile $profile, $location_id, $containers_count, $waste_qty = 0, $is_final = false)
    {
        $calc = $this->applyFinalizeWaste(
            $this->calculatePackaging($profile, $containers_count),
            $profile,
            $waste_qty,
            $is_final
        );
        $shortages = [];

        $bulk_available = $this->getAvailableStock($calc['bulk_variation_id'], $location_id);
        $bulk_required = $is_final ? $calc['bulk_to_deduct'] : $calc['bulk_consumed'];
        if ($bulk_available < $bulk_required) {
            $shortages[] = [
                'name' => __('manufacturing::lang.bulk_sauce'),
                'required' => $bulk_required,
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
