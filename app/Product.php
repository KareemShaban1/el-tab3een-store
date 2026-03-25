<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $appends = ['image_url'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sub_unit_ids' => 'array',
    ];

    /**
     * Get the products image.
     *
     * @return string
     */
    public function getImageUrlAttribute()
    {
        if (! empty($this->image)) {
            $image_url = asset('/uploads/img/'.rawurlencode($this->image));
        } else {
            $image_url = asset('/img/default.png');
        }

        return $image_url;
    }

    /**
     * Get the products image path.
     *
     * @return string
     */
    public function getImagePathAttribute()
    {
        if (! empty($this->image)) {
            $image_path = public_path('uploads').'/'.config('constants.product_img_path').'/'.$this->image;
        } else {
            $image_path = null;
        }

        return $image_path;
    }

    public function product_variations()
    {
        return $this->hasMany(\App\ProductVariation::class);
    }

    /**
     * Get the brand associated with the product.
     */
    public function brand()
    {
        return $this->belongsTo(\App\Brands::class);
    }

    /**
     * Get the unit associated with the product.
     */
    public function unit()
    {
        return $this->belongsTo(\App\Unit::class);
    }

    /**
     * Get the unit associated with the product.
     */
    public function second_unit()
    {
        return $this->belongsTo(\App\Unit::class, 'secondary_unit_id');
    }

    /**
     * Get category associated with the product.
     */
    public function category()
    {
        return $this->belongsTo(\App\Category::class);
    }

    /**
     * Get sub-category associated with the product.
     */
    public function sub_category()
    {
        return $this->belongsTo(\App\Category::class, 'sub_category_id', 'id');
    }

    /**
     * Get the tax associated with the product.
     */
    public function product_tax()
    {
        return $this->belongsTo(\App\TaxRate::class, 'tax', 'id');
    }

    /**
     * Get the variations associated with the product.
     */
    public function variations()
    {
        return $this->hasMany(\App\Variation::class);
    }

    /**
     * If product type is modifier get products associated with it.
     */
    public function modifier_products()
    {
        return $this->belongsToMany(\App\Product::class, 'res_product_modifier_sets', 'modifier_set_id', 'product_id');
    }

    /**
     * If product type is modifier get products associated with it.
     */
    public function modifier_sets()
    {
        return $this->belongsToMany(\App\Product::class, 'res_product_modifier_sets', 'product_id', 'modifier_set_id');
    }

    /**
     * Get the purchases associated with the product.
     */
    public function purchase_lines()
    {
        return $this->hasMany(\App\PurchaseLine::class);
    }

    /**
     * Scope a query to only include active products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('products.is_inactive', 0);
    }

    /**
     * Scope a query to only include inactive products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('products.is_inactive', 1);
    }

    /**
     * Scope a query to only include products for sales.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProductForSales($query)
    {
        return $query->where('not_for_selling', 0);
    }

    /**
     * Scope a query to only include products not for sales.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProductNotForSales($query)
    {
        return $query->where('not_for_selling', 1);
    }

    public function product_locations()
    {
        return $this->belongsToMany(\App\BusinessLocation::class, 'product_locations', 'product_id', 'location_id');
    }

    /**
     * Scope a query to only include products available for a location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForLocation($query, $location_id)
    {
        return $query->where(function ($q) use ($location_id) {
            $q->whereHas('product_locations', function ($query) use ($location_id) {
                $query->where('product_locations.location_id', $location_id);
            });
        });
    }

    /**
     * Get warranty associated with the product.
     */
    public function warranty()
    {
        return $this->belongsTo(\App\Warranty::class);
    }

    public function media()
    {
        return $this->morphMany(\App\Media::class, 'model');
    }

    public function rack_details()
    {
        return $this->hasMany(\App\ProductRack::class);
    }

    /**
     * Check whether this product is in stock.
     *
     * Stock comes from either:
     * - the product itself when `enable_stock != 1` (stock checking disabled), OR
     * - any of its variations having `qty_available > 0` (optionally for a given location).
     *
     * Note: this is intended for direct checks; for query-level filtering prefer `scopeInStock()`.
     */
    public function hasStock(?int $location_id = null): bool
    {
        $enableStock = (int) ($this->enable_stock ?? 0);
        if ($enableStock !== 1) {
            return true; // stock control disabled => treat as available
        }

        $variationsQuery = $this->variations()
            ->whereHas('variation_location_details', function ($q) use ($location_id) {
                $q->where('qty_available', '>', 0);

                if ($location_id !== null) {
                    $q->where('location_id', $location_id);
                }
            });

        return $variationsQuery->exists();
    }

    /**
     * Check if this product has stock in ANY location that belongs to the given business.
     */
    public function hasStockInBusiness(int $business_id): bool
    {
        $enableStock = (int) ($this->enable_stock ?? 0);
        if ($enableStock !== 1) {
            return true;
        }

        $location_ids = \App\BusinessLocation::where('business_id', $business_id)->pluck('id');

        // If business has no locations, then there is no stock.
        if ($location_ids->isEmpty()) {
            return false;
        }

        return $this->variations()
            ->whereHas('variation_location_details', function ($q) use ($location_ids) {
                $q->whereIn('location_id', $location_ids)->where('qty_available', '>', 0);
            })
            ->exists();
    }

    /**
     * Get total available qty across ALL business locations (sum of variation_location_details.qty_available).
     */
    public function businessStockQty(int $business_id): float
    {
        $location_ids = \App\BusinessLocation::where('business_id', $business_id)->pluck('id');
        if ($location_ids->isEmpty()) {
            return 0.0;
        }

        return (float) $this->variations()
            ->whereHas('variation_location_details', function ($q) use ($location_ids) {
                $q->whereIn('location_id', $location_ids);
            })
            ->with(['variation_location_details' => function ($q) use ($location_ids) {
                $q->whereIn('location_id', $location_ids);
            }])
            ->get()
            ->sum(function ($variation) use ($location_ids) {
                return (float) $variation->variation_location_details->sum('qty_available');
            });
    }

    /**
     * Query scope: only include products that are sellable and have available stock.
     *
     * Usage:
     *   Product::query()->inStock($location_id)->paginate(...)
     */
    public function scopeInStock($query, ?int $location_id = null)
    {
        return $query->where(function ($q) use ($location_id) {
            // If stock checking is disabled, consider the product available.
            $q->where('enable_stock', '!=', 1);

            // Otherwise, require at least one variation with available qty.
            $q->orWhereHas('variations.variation_location_details', function ($vdq) use ($location_id) {
                $vdq->where('qty_available', '>', 0);

                if ($location_id !== null) {
                    $vdq->where('location_id', $location_id);
                }
            });
        });
    }

    /**
     * Query scope: only include products that have available stock in ANY location of the business.
     */
    public function scopeInStockByBusiness($query, int $business_id)
    {
        return $query->where(function ($q) use ($business_id) {
            $q->where('enable_stock', '!=', 1);

            $location_ids = \App\BusinessLocation::where('business_id', $business_id)->pluck('id');

            if ($location_ids->isEmpty()) {
                // No locations => only products with enable_stock != 1 should match (already handled above).
                return;
            }

            $q->orWhereHas('variations.variation_location_details', function ($vdq) use ($location_ids) {
                $vdq->whereIn('location_id', $location_ids)->where('qty_available', '>', 0);
            });
        });
    }
}
