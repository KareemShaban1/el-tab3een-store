<?php

namespace App\LocationsFees;

use Illuminate\Database\Eloquent\Model;

class Governorate extends Model
{
    protected $table = 'lf_governorates';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function cities()
    {
        return $this->hasMany(City::class, 'governorate_id');
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
