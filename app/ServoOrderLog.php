<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServoOrderLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'items' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'order_addresses' => 'array',
        'shipping_charges' => 'float',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
