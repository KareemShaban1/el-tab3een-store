<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoCode extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'is_single_use' => 'boolean',
        'restore_on_return' => 'boolean',
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function redemptions()
    {
        return $this->hasMany(DiscountRedemption::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'promo_code_id');
    }

    /**
     * Normalize code for storage/lookup.
     */
    public static function normalizeCode(?string $code): string
    {
        return strtoupper(trim((string) $code));
    }

    public static function forDropdown($business_id, $prepend_none = true)
    {
        $list = static::where('business_id', $business_id)
            ->where('is_active', 1)
            ->orderBy('code')
            ->pluck('code', 'id');

        if ($prepend_none) {
            $list = $list->prepend(__('lang_v1.none'), '');
        }

        return $list;
    }
}
