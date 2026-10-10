<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'include_sub_categories' => 'boolean',
        'apply_on_all_variations' => 'boolean',
        'apply_in_pos' => 'boolean',
        'apply_in_ecommerce' => 'boolean',
        'requires_promo_code' => 'boolean',
        'first_order_only' => 'boolean',
        'applicable_in_cg' => 'boolean',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function variations()
    {
        return $this->belongsToMany(\App\Variation::class, 'discount_variations', 'discount_id', 'variation_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class, 'brand_id');
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function customerGroups()
    {
        return $this->belongsToMany(
            CustomerGroup::class,
            'discount_customer_groups',
            'discount_id',
            'customer_group_id'
        );
    }

    public function promoCodes()
    {
        return $this->hasMany(PromoCode::class);
    }

    public function redemptions()
    {
        return $this->hasMany(DiscountRedemption::class);
    }

    /**
     * Sync legacy applicable_in_cg with cg_mode.
     */
    public function syncCgFlagsFromMode(): void
    {
        if ($this->cg_mode === 'with_group_only' || $this->cg_mode === 'specific_groups') {
            $this->applicable_in_cg = 1;
        } elseif ($this->cg_mode === 'any') {
            $this->applicable_in_cg = 0;
        }
    }
}
