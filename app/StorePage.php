<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StorePage extends Model
{
    public const FOOTER_GROUP_QUICK_LINKS = 'quick_links';

    public const FOOTER_GROUP_CUSTOMER_SERVICE = 'customer_service';

    public const FOOTER_GROUP_LEGAL = 'legal';

    public const PAGE_TYPE_PRIVACY = 'privacy';

    public const PAGE_TYPE_TERMS = 'terms';

    public const PAGE_TYPE_WARRANTY = 'warranty';

    public const PAGE_TYPE_RETURNS = 'returns';

    public const PAGE_TYPE_FAQ = 'faq';

    public const PAGE_TYPE_CUSTOM = 'custom';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_footer' => 'boolean',
        'show_in_header' => 'boolean',
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
        return $query->orderBy('sort_order')->orderBy('title');
    }

    public function getUrlAttribute(): string
    {
        return route('store.pages.show', $this->slug);
    }

    public static function footerGroups(): array
    {
        return [
            self::FOOTER_GROUP_QUICK_LINKS => __('store_pages.footer_group_quick_links'),
            self::FOOTER_GROUP_CUSTOMER_SERVICE => __('store_pages.footer_group_customer_service'),
            self::FOOTER_GROUP_LEGAL => __('store_pages.footer_group_legal'),
        ];
    }

    public static function pageTypes(): array
    {
        return [
            self::PAGE_TYPE_PRIVACY => __('store_pages.page_type_privacy'),
            self::PAGE_TYPE_TERMS => __('store_pages.page_type_terms'),
            self::PAGE_TYPE_WARRANTY => __('store_pages.page_type_warranty'),
            self::PAGE_TYPE_RETURNS => __('store_pages.page_type_returns'),
            self::PAGE_TYPE_FAQ => __('store_pages.page_type_faq'),
            self::PAGE_TYPE_CUSTOM => __('store_pages.page_type_custom'),
        ];
    }

    public static function normalizeSlug(?string $slug, ?string $title): string
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            $slug = Str::slug((string) $title);
        } else {
            $slug = Str::slug($slug);
        }

        if ($slug === '') {
            $slug = 'page-'.time();
        }

        return $slug;
    }
}
