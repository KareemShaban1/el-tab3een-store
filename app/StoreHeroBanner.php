<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StoreHeroBanner extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('business_id', $business_id);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        return asset('uploads/hero_banners/'.$this->image);
    }

    public static function defaultFallback(): self
    {
        return new self([
            'badge' => '🔥 أحدث تقنيات 2025',
            'title' => 'اكتشف <span>عالم التقنية</span><br>بأفضل الأسعار',
            'content' => 'تشكيلة ضخمة من أحدث الأجهزة الإلكترونية من أفضل الماركات العالمية. جودة عالية، ضمان أصلي، وتوصيل سريع لباب بيتك.',
            'link_title' => '🛒 تسوق الآن',
            'link_url' => null,
            'image' => 'https://placehold.co/460x400/3d3868/ffffff?text=iPhone+15+Pro+Max',
            'image_alt' => 'iPhone 15 Pro Max',
            'is_active' => true,
        ]);
    }
}
