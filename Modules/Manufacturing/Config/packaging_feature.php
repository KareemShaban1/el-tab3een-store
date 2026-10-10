<?php

/**
 * Master switch for the sauce / liquid packaging workflow.
 *
 * Set `enabled` to false to hide all packaging UI, routes, and product fields
 * without removing database columns (data stays safe).
 *
 * Per-business toggle: Manufacturing → Settings → "Enable packaging workflow"
 * (stored in business.manufacturing_settings JSON as enable_packaging_workflow).
 */
return [
    'enabled' => env('MFG_PACKAGING_FEATURE_ENABLED', true),

    /**
     * When true, POS product search hides products with product_usage_type = packaging_material.
     */
    'hide_packaging_materials_in_pos' => true,

    /**
     * Carton rounding policy: strict | allow_partial | round_up
     */
    'partial_carton_policy' => 'strict',

    'product_usage_types' => [
        'raw_ingredient' => 'Raw ingredient (recipe input)',
        'bulk_finished' => 'Bulk finished (kg/L — cooking output)',
        'packaging_material' => 'Packaging material (bottle, cap, carton…)',
        'packaged_finished' => 'Packaged finished (sellable carton/unit)',
    ],
];
