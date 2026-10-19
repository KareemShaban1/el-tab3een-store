<?php

namespace Modules\Manufacturing\Entities;

use Illuminate\Database\Eloquent\Model;

class MfgProductionPackagingUsage extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
    ];

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }

    public function productionPurchase()
    {
        return $this->belongsTo(\App\Transaction::class, 'production_purchase_id');
    }
}
