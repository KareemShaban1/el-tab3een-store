<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiscountRedemption extends Model
{
    protected $guarded = ['id'];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function sellLine()
    {
        return $this->belongsTo(TransactionSellLine::class, 'transaction_sell_line_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
