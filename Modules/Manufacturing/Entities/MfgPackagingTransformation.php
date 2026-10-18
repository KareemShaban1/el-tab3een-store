<?php

namespace Modules\Manufacturing\Entities;

use Illuminate\Database\Eloquent\Model;

class MfgPackagingTransformation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'carton_qty' => 'float',
        'unit_qty' => 'float',
        'transformed_at' => 'datetime',
    ];

    public function profile()
    {
        return $this->belongsTo(MfgPackagingProfile::class, 'packaging_profile_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function cartonVariation()
    {
        return $this->belongsTo(\App\Variation::class, 'carton_variation_id');
    }

    public function unitVariation()
    {
        return $this->belongsTo(\App\Variation::class, 'unit_variation_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function productionPurchase()
    {
        return $this->belongsTo(\App\Transaction::class, 'production_purchase_id');
    }

    public function productionSell()
    {
        return $this->belongsTo(\App\Transaction::class, 'production_sell_id');
    }
}
