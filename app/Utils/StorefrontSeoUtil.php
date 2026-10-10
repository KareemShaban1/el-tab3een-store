<?php

namespace App\Utils;

use App\Http\Controllers\Frontend\StorefrontController;
use App\StorefrontSeoPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StorefrontSeoUtil
{
    /**
     * Resolve SEO data for the current frontend request (static pages).
     *
     * @return array{meta_title: ?string, meta_description: ?string, meta_keywords: ?string, og_image: ?string, robots: ?string, canonical: ?string, page_key: ?string}
     */
    public function forCurrentRequest(?Request $request = null): array
    {
        $request = $request ?: request();
        $empty = $this->emptySeo();

        $routeName = optional($request->route())->getName();
        if (! is_string($routeName) || $routeName === '') {
            return $empty;
        }

        // Account / reset-password: private pages — noindex.
        if (Str::startsWith($routeName, 'store.account.') || $routeName === 'store.auth.password.reset.form') {
            return array_merge($empty, [
                'robots' => 'noindex, nofollow',
                'canonical' => $request->url(),
            ]);
        }

        if (in_array($routeName, ['store.products.show', 'store.tab3een.products.show', 'store.pages.show'], true)) {
            return array_merge($empty, [
                'canonical' => $request->url(),
            ]);
        }

        $pageKey = StorefrontSeoPage::routePageMap()[$routeName] ?? null;
        if ($pageKey === null) {
            return array_merge($empty, [
                'canonical' => $request->url(),
            ]);
        }

        $seo = $this->forPageKey(
            StorefrontController::resolveBusinessId($request),
            $pageKey
        );
        $seo['page_key'] = $pageKey;
        $seo['canonical'] = $request->url();

        return $seo;
    }

    /**
     * @return array{meta_title: ?string, meta_description: ?string, meta_keywords: ?string, og_image: ?string, robots: ?string, canonical: ?string, page_key: ?string}
     */
    public function forPageKey(int $businessId, string $pageKey): array
    {
        $seo = $this->emptySeo();
        $seo['page_key'] = $pageKey;

        if (! Schema::hasTable('storefront_seo_pages')) {
            return $seo;
        }

        $row = StorefrontSeoPage::query()
            ->where('business_id', $businessId)
            ->where('page_key', $pageKey)
            ->first();

        if (! $row) {
            return $seo;
        }

        return array_merge($seo, $row->toSeoArray());
    }

    /**
     * Build SEO payload from a local product (or generic name/description array).
     *
     * @param  object|array<string, mixed>  $product
     * @return array{meta_title: ?string, meta_description: ?string, meta_keywords: ?string, og_image: ?string, robots: ?string, canonical: ?string, page_key: ?string}
     */
    public function forProduct($product, ?string $canonical = null): array
    {
        $name = $this->scalar($this->attr($product, 'name'));
        $metaTitle = $this->scalar($this->attr($product, 'meta_title'));
        $metaDescription = $this->scalar($this->attr($product, 'meta_description'));
        $metaKeywords = $this->scalar($this->attr($product, 'meta_keywords'));
        $description = $this->scalar($this->attr($product, 'product_description'))
            ?: $this->scalar($this->attr($product, 'description'));
        $image = $this->scalar($this->attr($product, 'image_url'))
            ?: $this->scalar($this->attr($product, 'image'));

        if ($metaDescription === '' && $description !== '') {
            $metaDescription = Str::limit(trim(strip_tags($description)), 160, '');
        }

        return [
            'meta_title' => $metaTitle !== '' ? $metaTitle : ($name !== '' ? $name : null),
            'meta_description' => $metaDescription !== '' ? $metaDescription : null,
            'meta_keywords' => $metaKeywords !== '' ? $metaKeywords : null,
            'og_image' => $image !== '' ? $image : null,
            'robots' => null,
            'canonical' => $canonical,
            'page_key' => 'product',
        ];
    }

    /**
     * @return array{meta_title: ?string, meta_description: ?string, meta_keywords: ?string, og_image: ?string, robots: ?string, canonical: ?string, page_key: ?string}
     */
    private function emptySeo(): array
    {
        return [
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
            'og_image' => null,
            'robots' => null,
            'canonical' => null,
            'page_key' => null,
        ];
    }

    /**
     * @param  object|array<string, mixed>  $source
     */
    private function attr($source, string $key): mixed
    {
        if (is_array($source)) {
            return $source[$key] ?? null;
        }

        return $source->{$key} ?? null;
    }

    private function scalar(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
