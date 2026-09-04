<?php

namespace Modules\Manufacturing\Entities;

use Illuminate\Database\Eloquent\Model;

class MfgPackagingMaterial extends Model
{
    protected $guarded = ['id'];

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }

    public function subUnit()
    {
        return $this->belongsTo(\App\Unit::class, 'sub_unit_id');
    }

    public function profile()
    {
        return $this->belongsTo(MfgPackagingProfile::class, 'packaging_profile_id');
    }
}