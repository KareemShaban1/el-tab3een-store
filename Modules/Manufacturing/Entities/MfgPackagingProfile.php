<?php

namespace Modules\Manufacturing\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MfgPackagingProfile extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'uses_carton' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Whether this profile packs containers into outer cartons.
     * When false, finished output is containers/bottles only.
     */
    public function usesCarton(): bool
    {
        // Default true for legacy rows without the column hydrated
        return (bool) ($this->uses_carton ?? true);
    }

    public function bulkVariation()
    {
        return $this->belongsTo(\App\Variation::class, 'bulk_variation_id');
    }

    public function outputVariation()
    {
        return $this->belongsTo(\App\Variation::class, 'output_variation_id');
    }

    /**
     * Sellable unit product (bottle/bag) used when unpacking cartons.
     */
    public function unitVariation()
    {
        return $this->belongsTo(\App\Variation::class, 'unit_variation_id');
    }

    public function materials()
    {
        return $this->hasMany(MfgPackagingMaterial::class, 'packaging_profile_id');
    }

    public function canTransformCartonToUnit(): bool
    {
        return $this->usesCarton()
            && ! empty($this->unit_variation_id)
            && (int) $this->units_per_carton > 0
            && ! empty($this->output_variation_id)
            && (int) $this->unit_variation_id !== (int) $this->output_variation_id;
    }

    public static function forDropdown($business_id)
    {
        return static::where('business_id', $business_id)
            ->where('is_active', 1)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Profiles that support carton → bottle/bag transformation.
     */
    public static function forTransformDropdown($business_id)
    {
        return static::where('business_id', $business_id)
            ->where('is_active', 1)
            ->where('uses_carton', 1)
            ->whereNotNull('unit_variation_id')
            ->where('units_per_carton', '>', 0)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public static function variationLabel($variation)
    {
        if (empty($variation) || empty($variation->product)) {
            return '';
        }

        $product = $variation->product;
        $pv = $variation->product_variation;

        if ($product->type == 'variable' && ! empty($pv)) {
            return $product->name . ' - ' . $pv->name . ' - ' . $variation->name . ' (' . $variation->sub_sku . ')';
        }

        return $product->name . ' (' . $variation->sub_sku . ')';
    }

    public static function formattedName($profile)
    {
        $profile->loadMissing(['bulkVariation.product', 'bulkVariation.product_variation', 'outputVariation.product', 'outputVariation.product_variation']);

        $bulk = self::variationLabel($profile->bulkVariation);
        $output = self::variationLabel($profile->outputVariation);

        return $profile->name . ' [' . $bulk . ' → ' . $output . ']';
    }
}