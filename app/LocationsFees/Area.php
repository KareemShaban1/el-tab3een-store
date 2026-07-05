<?php

namespace App\LocationsFees;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'lf_areas';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'delivery_cost' => 'float',
    ];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where($query->getModel()->getTable().'.business_id', $business_id);
    }
}
