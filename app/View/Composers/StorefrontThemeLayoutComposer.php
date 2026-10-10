<?php

namespace App\View\Composers;

use App\Category;
use App\Http\Controllers\Frontend\StorefrontController;
use App\StorefrontSetting;
use App\StorePage;
use App\Utils\StorefrontSeoUtil;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StorefrontThemeLayoutComposer
{
    /**
     * Featured + active-in-app parent categories for the header filter dropdown.
     */
    public function compose(View $view): void
    {
        $request = request();
        $businessId = StorefrontController::resolveBusinessId($request);

        $storeHeaderFeaturedCategories = Category::query()
            ->where('business_id', $businessId)
            ->where('category_type', 'product')
            ->where('parent_id', 0)
            ->featured()
            ->activeInApp()
            ->storefrontSortOrder()
            ->select('id', 'name')
            ->get();

        $storeFooterPages = collect();
        $storeHeaderPages = collect();

        if (Schema::hasTable('store_pages')) {
            $pages = StorePage::forBusiness($businessId)->active()->ordered()->get();
            $storeFooterPages = $pages->where('show_in_footer', true)->groupBy('footer_group');
            $storeHeaderPages = $pages->where('show_in_header', true)->values();
        }

        $storeAppearance = StorefrontSetting::defaultContent();
        $storeAppearanceModel = null;
        $whatsapp = null;

        if (Schema::hasTable('storefront_settings')) {
            $storeAppearanceModel = StorefrontSetting::forBusiness($businessId);
            $storeAppearance = $storeAppearanceModel->content();
            $storeAppearance['announce_link_url'] = $storeAppearanceModel->announceLinkUrl();
            $storeAppearance['support_phone'] = $storeAppearanceModel->supportPhone();
            $storeAppearance['support_email'] = $storeAppearanceModel->supportEmail();
            $storeAppearance['copyright_text'] = $storeAppearanceModel->copyrightText();

            if ($storeAppearanceModel->shouldShowWhatsappButton()) {
                $whatsapp = [
                    'url' => $storeAppearanceModel->whatsappUrl(),
                    'label' => __('lang_v1.storefront_whatsapp_chat'),
                ];
            }
        } else {
            $storeAppearance['announce_link_url'] = route('store.products.index');
            $storeAppearance['copyright_text'] = '© '.date('Y').' '
                .($storeAppearance['brand_name'] ?? '')
                .' '
                .($storeAppearance['brand_name_highlight'] ?? '')
                .'. جميع الحقوق محفوظة.';
        }

        $viewData = $view->getData();
        $seo = app(StorefrontSeoUtil::class)->forCurrentRequest($request);

        // Controllers (CMS pages / products) may already provide title & description.
        if (empty($viewData['title']) && ! empty($seo['meta_title'])) {
            $view->with('title', $seo['meta_title']);
        }
        if (empty($viewData['metaDescription']) && ! empty($seo['meta_description'])) {
            $view->with('metaDescription', $seo['meta_description']);
        }
        if (empty($viewData['metaKeywords']) && ! empty($seo['meta_keywords'])) {
            $view->with('metaKeywords', $seo['meta_keywords']);
        }
        if (empty($viewData['seoOgImage']) && ! empty($seo['og_image'])) {
            $view->with('seoOgImage', $seo['og_image']);
        }
        if (empty($viewData['seoRobots']) && ! empty($seo['robots'])) {
            $view->with('seoRobots', $seo['robots']);
        }
        if (empty($viewData['seoCanonical']) && ! empty($seo['canonical'])) {
            $view->with('seoCanonical', $seo['canonical']);
        }

        $view->with([
            'storeHeaderFeaturedCategories' => $storeHeaderFeaturedCategories,
            'storeSearchSuggestUrl' => route('store.search.suggest'),
            'storeFooterPages' => $storeFooterPages,
            'storeHeaderPages' => $storeHeaderPages,
            'storeAppearance' => $storeAppearance,
            'storeAppearanceSettings' => $storeAppearanceModel,
            'storefrontSeo' => $seo,
            'whatsapp' => $whatsapp,
        ]);
    }
}
