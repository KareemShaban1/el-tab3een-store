<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WebsiteVisitLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_bot' => 'boolean',
        'events' => 'array',
        'time_spent_seconds' => 'integer',
        'events_count' => 'integer',
        'last_activity_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeHumans(Builder $query): Builder
    {
        return $query->where('is_bot', false);
    }

    public function scopeBots(Builder $query): Builder
    {
        return $query->where('is_bot', true);
    }

    public function locationDisplay(): string
    {
        if (! empty($this->location_label)) {
            return (string) $this->location_label;
        }

        $parts = array_values(array_filter([
            $this->city,
            $this->region && $this->region !== $this->city ? $this->region : null,
            $this->country ?: $this->country_code,
        ]));

        return $parts !== [] ? implode(', ', $parts) : '';
    }

    public static function pageTypes(): array
    {
        return [
            'home' => __('website_logs.page_type_home'),
            'products' => __('website_logs.page_type_products'),
            'product' => __('website_logs.page_type_product'),
            'tab3een_catalog' => __('website_logs.page_type_tab3een_catalog'),
            'tab3een_product' => __('website_logs.page_type_tab3een_product'),
            'categories' => __('website_logs.page_type_categories'),
            'flash_deals' => __('website_logs.page_type_flash_deals'),
            'search' => __('website_logs.page_type_search'),
            'page' => __('website_logs.page_type_page'),
            'checkout' => __('website_logs.page_type_checkout'),
            'account' => __('website_logs.page_type_account'),
            'auth' => __('website_logs.page_type_auth'),
            'other' => __('website_logs.page_type_other'),
        ];
    }

    public static function eventTypeMeta(): array
    {
        return [
            'add_to_cart' => [
                'label' => __('website_logs.event_add_to_cart'),
                'class' => 'label-success',
                'icon' => 'fa-shopping-cart',
            ],
            'filter_applied' => [
                'label' => __('website_logs.event_filter_applied'),
                'class' => 'label-info',
                'icon' => 'fa-filter',
            ],
            'wishlist_toggle' => [
                'label' => __('website_logs.event_wishlist'),
                'class' => 'label-warning',
                'icon' => 'fa-heart',
            ],
            'quick_view' => [
                'label' => __('website_logs.event_quick_view'),
                'class' => 'label-primary',
                'icon' => 'fa-eye',
            ],
            'checkout_click' => [
                'label' => __('website_logs.event_checkout'),
                'class' => 'label-danger',
                'icon' => 'fa-credit-card',
            ],
            'open_cart' => [
                'label' => __('website_logs.event_open_cart'),
                'class' => 'label-default',
                'icon' => 'fa-shopping-basket',
            ],
            'form_submit' => [
                'label' => __('website_logs.event_form_submit'),
                'class' => 'label-default',
                'icon' => 'fa-paper-plane',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|mixed  $event
     */
    public static function formatEventHtml($event): string
    {
        if (! is_array($event)) {
            return '';
        }

        $type = (string) ($event['type'] ?? 'event');
        $label = trim((string) ($event['label'] ?? ''));
        $meta = is_array($event['meta'] ?? null) ? $event['meta'] : [];
        $types = self::eventTypeMeta();
        $typeMeta = $types[$type] ?? [
            'label' => ucwords(str_replace('_', ' ', $type)),
            'class' => 'label-default',
            'icon' => 'fa-bolt',
        ];

        $detail = $label;
        if ($type === 'add_to_cart') {
            $product = $label !== '' && $label !== 'add_to_cart'
                ? $label
                : (string) ($meta['id'] ?? '');
            $detail = $product !== ''
                ? __('website_logs.event_add_to_cart_detail', ['product' => $product])
                : __('website_logs.event_add_to_cart');
            if (! empty($meta['price'])) {
                $detail .= ' · '.__('website_logs.event_price', ['price' => $meta['price']]);
            }
        } elseif ($type === 'filter_applied') {
            $detail = $label !== '' ? $label : __('website_logs.event_filter_applied');
        } elseif ($type === 'wishlist_toggle') {
            $detail = __('website_logs.event_wishlist_detail', [
                'id' => $meta['id'] ?? ($label ?: '-'),
            ]);
        } elseif ($type === 'quick_view') {
            $detail = __('website_logs.event_quick_view_detail', [
                'id' => $meta['id'] ?? ($label ?: '-'),
            ]);
        } elseif ($label !== '' && $label !== $type) {
            $detail = $label;
        } else {
            $detail = $typeMeta['label'];
        }

        return '<div class="website-log-event" style="margin-bottom:4px;">'
            .'<span class="label '.$typeMeta['class'].'" style="display:inline-block;margin-inline-end:4px;">'
            .'<i class="fa '.$typeMeta['icon'].'"></i> '.e($typeMeta['label'])
            .'</span>'
            .'<span>'.e($detail).'</span>'
            .'</div>';
    }
}
