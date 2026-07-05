<?php

namespace App\LocationsFees;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'lf_cities';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'delivery_cost' => 'float',
    ];

    public function governorate()
    {
        return $this->belongsTo(Governorate::class, 'governorate_id');
    }

    public function areas()
    {
        return $this->hasMany(Area::class, 'city_id');
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
