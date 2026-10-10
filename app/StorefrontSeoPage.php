<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StorefrontSeoPage extends Model
{
    protected $guarded = ['id'];

    /**
     * Static storefront pages that can be managed from the dashboard.
     *
     * @return array<string, array{label_key: string, route_hint?: string}>
     */
    public static function pageDefinitions(): array
    {
        return [
            'welcome' => [
                'label_key' => 'storefront_seo.pages.welcome',
                'route_hint' => '/',
            ],
            'home' => [
                'label_key' => 'storefront_seo.pages.home',
                'route_hint' => '/store',
            ],
            'products' => [
                'label_key' => 'storefront_seo.pages.products',
                'route_hint' => '/store/products',
            ],
            'search' => [
                'label_key' => 'storefront_seo.pages.search',
                'route_hint' => '/store/search',
            ],
            'checkout' => [
                'label_key' => 'storefront_seo.pages.checkout',
                'route_hint' => '/store/checkout',
            ],
            'login' => [
                'label_key' => 'storefront_seo.pages.login',
                'route_hint' => '/store/login',
            ],
            'register' => [
                'label_key' => 'storefront_seo.pages.register',
                'route_hint' => '/store/register',
            ],
            'forgot_password' => [
                'label_key' => 'storefront_seo.pages.forgot_password',
                'route_hint' => '/store/forgot-password',
            ],
            'repair_status' => [
                'label_key' => 'storefront_seo.pages.repair_status',
                'route_hint' => '/repair-status',
            ],
        ];
    }

    /**
     * Map named routes to SEO page keys.
     *
     * @return array<string, string>
     */
    public static function routePageMap(): array
    {
        return [
            'welcome' => 'welcome',
            'store.home' => 'home',
            'store.products.index' => 'products',
            'store.search' => 'search',
            'store.checkout.form' => 'checkout',
            'store.auth.login.form' => 'login',
            'store.auth.register.form' => 'register',
            'store.auth.password.request' => 'forgot_password',
            'repair-status' => 'repair_status',
        ];
    }

    /**
     * @return array{meta_title: ?string, meta_description: ?string, meta_keywords: ?string, og_image: ?string, robots: ?string}
     */
    public function toSeoArray(): array
    {
        return [
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'og_image' => $this->og_image,
            'robots' => $this->robots,
        ];
    }
}
