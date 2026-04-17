<?php

namespace App\View\Composers;

use App\Category;
use App\Http\Controllers\Frontend\StorefrontController;
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

        $view->with([
            'storeHeaderFeaturedCategories' => $storeHeaderFeaturedCategories,
            'storeSearchSuggestUrl' => route('store.search.suggest'),
        ]);
    }
}
