<?php

namespace Modules\Manufacturing\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MfgPackagingProfile extends Model
{
    protected $guarded = ['id'];

    public function bulkVariation()
    {
        return $this->belongsTo(\App\Variation::class, 'bulk_variation_id');
    }

    public function outputVariation()
    {
        return $this->belongsTo(\App\Variation::class, 'output_variation_id');
    }

    public function materials()
    {
        return $this->hasMany(MfgPackagingMaterial::class, 'packaging_profile_id');
    }

    public static function forDropdown($business_id)
    {
        return static::where('business_id', $business_id)
            ->where('is_active', 1)
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