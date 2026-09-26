<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Tab3eenCatalogService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCatalog(?int $categoryId = null): array
    {
        $url = trim((string) config('storefront.tab3een_catalog_api_url', ''));

        if ($url === '') {
            return [];
        }

        try {
            $rawData = $this->fetchCatalogPayload($url, null);

            if ($rawData === null) {
                return [];
            }

            $categories = $this->normalizeCategories($rawData);

            if ($categoryId !== null && $categoryId > 0) {
                $categories = collect($categories)
                    ->filter(function ($category) use ($categoryId) {
                        $aliasIds = array_map('intval', $category['alias_ids'] ?? []);

                        return (int) ($category['id'] ?? 0) === $categoryId
                            || (int) ($category['category_id'] ?? 0) === $categoryId
                            || in_array($categoryId, $aliasIds, true);
                    })
                    ->values()
                    ->all();
            }

            return $categories;
        } catch (\Throwable $e) {
            Log::warning('Tab3een catalog API exception: '.$e->getMessage(), [
                'url' => $url,
                'category_id' => $categoryId,
            ]);

            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function fetchCatalogPayload(string $url, ?int $categoryId): ?array
    {
        if ($categoryId !== null && $categoryId > 0) {
            $postResponse = Http::timeout(15)
                ->acceptJson()
                ->post($url, [
                    'category_id' => $categoryId,
                ]);

            if ($postResponse->successful()) {
                $body = $postResponse->json();
                if (($body['status'] ?? null) === 'success' && is_array($body['data'] ?? null)) {
                    return $body['data'];
                }
            }

            $getFiltered = Http::timeout(15)
                ->acceptJson()
                ->get($url, [
                    'category_id' => $categoryId,
                ]);

            if ($getFiltered->successful()) {
                $body = $getFiltered->json();
                if (($body['status'] ?? null) === 'success' && is_array($body['data'] ?? null)) {
                    return $body['data'];
                }
            }
        }

        $response = Http::timeout(15)
            ->acceptJson()
            ->get($url);

        if (! $response->successful()) {
            Log::warning('Tab3een catalog API request failed.', [
                'url' => $url,
                'status' => $response->status(),
                'category_id' => $categoryId,
            ]);

            return null;
        }

        $body = $response->json();
        if (($body['status'] ?? null) !== 'success' || ! is_array($body['data'] ?? null)) {
            return null;
        }

        return $body['data'];
    }

    /**
     * Fetch live stock for a Servo variation (uses raw API, not filtered catalog).
     */
    public function getVariationStock(int $productId, int $variationId): ?float
    {
        $url = trim((string) config('storefront.tab3een_catalog_api_url', ''));

        if ($url === '') {
            return null;
        }

        try {
            $response = Http::timeout(15)->acceptJson()->get($url);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->json();
            if (($body['status'] ?? null) !== 'success' || ! is_array($body['data'] ?? null)) {
                return null;
            }

            foreach ($body['data'] as $category) {
                foreach ($category['products'] ?? [] as $product) {
                    if ((int) ($product['id'] ?? 0) !== $productId) {
                        continue;
                    }
                    foreach ($product['variations'] ?? [] as $variation) {
                        if ((int) ($variation['id'] ?? 0) === $variationId) {
                            return (float) ($variation['total_qty_available'] ?? 0);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Tab3een stock lookup failed: '.$e->getMessage(), [
                'product_id' => $productId,
                'variation_id' => $variationId,
            ]);
        }

        return null;
    }

    /**
     * Full product/variation lookup from raw catalog (no in-stock filter).
     *
     * @return array<string, array{product_name: string, variation_name: string, price: float|null}>
     */
    public function getVariationLookupMap(): array
    {
        $url = trim((string) config('storefront.tab3een_catalog_api_url', ''));

        if ($url === '') {
            return [];
        }

        try {
            $response = Http::timeout(15)->acceptJson()->get($url);

            if (! $response->successful()) {
                return [];
            }

            $body = $response->json();
            if (($body['status'] ?? null) !== 'success' || ! is_array($body['data'] ?? null)) {
                return [];
            }

            $map = [];

            foreach ($body['data'] as $category) {
                foreach ($category['products'] ?? [] as $product) {
                    $productId = (int) ($product['id'] ?? 0);
                    $productName = (string) ($product['name'] ?? '');

                    foreach ($product['variations'] ?? [] as $variation) {
                        $variationId = (int) ($variation['id'] ?? 0);
                        if ($productId <= 0 || $variationId <= 0) {
                            continue;
                        }

                        $map[$productId.'-'.$variationId] = [
                            'product_name' => $productName,
                            'variation_name' => (string) ($variation['name'] ?? ''),
                            'price' => isset($variation['price']) ? (float) $variation['price'] : null,
                        ];
                    }
                }
            }

            return $map;
        } catch (\Throwable $e) {
            Log::warning('Tab3een variation lookup map failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCategories(array $categories): array
    {
        $normalized = collect($categories)
            ->map(function ($category) {
                $products = collect($category['products'] ?? [])
                    ->map(function ($product) {
                        $variations = collect($product['variations'] ?? [])
                            ->map(function ($variation) {
                                $price = $variation['price'] ?? null;

                                return [
                                    'variation_id' => (int) ($variation['id'] ?? 0),
                                    'name' => (string) ($variation['name'] ?? 'Default'),
                                    'sku' => (string) ($variation['sku'] ?? ''),
                                    'price' => $price !== null ? (float) $price : null,
                                    'qty_available' => (float) ($variation['total_qty_available'] ?? 0),
                                ];
                            })
                            ->filter(fn ($variation) => $variation['variation_id'] > 0
                                && $variation['qty_available'] >= 1
                                && $variation['price'] !== null)
                            ->values()
                            ->all();

                        $defaultVariation = $variations[0] ?? null;

                        return [
                            'id' => (int) ($product['id'] ?? 0),
                            'name' => (string) ($product['name'] ?? ''),
                            'description' => (string) ($product['description'] ?? ''),
                            'image_url' => (string) ($product['image_url'] ?? ''),
                            'default_variation_id' => (int) ($defaultVariation['variation_id'] ?? 0),
                            'default_price' => $defaultVariation['price'] ?? null,
                            'has_price' => $defaultVariation !== null && $defaultVariation['price'] !== null,
                            'variations' => $variations,
                        ];
                    })
                    ->filter(fn ($product) => $product['id'] > 0
                        && $product['default_price'] !== null
                        && ! empty($product['variations']))
                    ->values()
                    ->all();

                [$categoryName, $subCategoryName] = $this->categoryLabelParts($category, true);
                $displayName = $categoryName !== '' ? $categoryName : $subCategoryName;

                return [
                    'id' => (int) ($category['id'] ?? $category['category_id'] ?? $category['sub_category_id'] ?? 0),
                    'category_id' => ! empty($category['category_id']) ? (int) $category['category_id'] : null,
                    'sub_category_id' => ! empty($category['sub_category_id']) ? (int) $category['sub_category_id'] : null,
                    'category_name' => $categoryName,
                    'sub_category_name' => $subCategoryName,
                    'name' => $displayName,
                    'image' => (string) ($category['image'] ?? ''),
                    'sort_order' => (int) ($category['sort_order'] ?? 0),
                    'products' => $products,
                ];
            })
            ->filter(fn ($category) => ! empty($category['products']))
            ->values();

        return $this->groupCategoriesByParent($normalized);
    }

    /**
     * Rows that share a Servo category are one category. Subcategory ids stay as aliases
     * so older links still open that category.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $categories
     * @return array<int, array<string, mixed>>
     */
    private function groupCategoriesByParent($categories): array
    {
        return $categories
            ->groupBy(function ($category) {
                $categoryId = (int) ($category['category_id'] ?? 0);

                return $categoryId > 0 ? 'category-'.$categoryId : 'row-'.(int) ($category['id'] ?? 0);
            })
            ->map(function ($group) {
                $first = $group->first();
                $categoryId = (int) ($first['category_id'] ?? 0);
                $products = $group
                    ->flatMap(fn ($category) => $category['products'] ?? [])
                    ->unique('id')
                    ->values()
                    ->all();
                $aliasIds = $group
                    ->flatMap(function ($category) {
                        return [
                            (int) ($category['id'] ?? 0),
                            (int) ($category['category_id'] ?? 0),
                            (int) ($category['sub_category_id'] ?? 0),
                        ];
                    })
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();
                $withImage = $group->first(fn ($category) => trim((string) ($category['image'] ?? '')) !== '');
                $image = (string) ($withImage['image'] ?? '');

                return [
                    'id' => $categoryId > 0 ? $categoryId : (int) ($first['id'] ?? 0),
                    'category_id' => $categoryId > 0 ? $categoryId : null,
                    'sub_category_id' => null,
                    'alias_ids' => $aliasIds,
                    'category_name' => (string) ($first['category_name'] ?? ''),
                    'sub_category_name' => '',
                    'name' => (string) ($first['name'] ?? ''),
                    'image' => $image,
                    'sort_order' => (int) $group->min('sort_order'),
                    'products' => $products,
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null Normalized product payload for storefront views.
     */
    public function getProduct(int $productId): ?array
    {
        $url = $this->productApiUrl($productId);

        if ($url === '') {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get($url);

            if (! $response->successful()) {
                Log::warning('Tab3een product API request failed.', [
                    'url' => $url,
                    'status' => $response->status(),
                    'product_id' => $productId,
                ]);

                return null;
            }

            $body = $response->json();
            if (($body['status'] ?? null) !== 'success' || ! is_array($body['data'] ?? null)) {
                return null;
            }

            return $this->normalizeProduct($body['data']);
        } catch (\Throwable $e) {
            Log::warning('Tab3een product API exception: '.$e->getMessage(), [
                'url' => $url,
                'product_id' => $productId,
            ]);

            return null;
        }
    }

    private function catalogBaseUrl(): string
    {
        return rtrim(trim((string) config('storefront.tab3een_catalog_api_url', '')), '/');
    }

    private function productApiUrl(int $productId): string
    {
        $base = $this->catalogBaseUrl();

        if ($base === '' || $productId <= 0) {
            return '';
        }

        return $base.'/products/'.$productId;
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>|null
     */
    private function normalizeProduct(array $product): ?array
    {
        $variations = collect($product['variations'] ?? [])
            ->map(function ($variation) {
                $price = $variation['price'] ?? null;

                return [
                    'variation_id' => (int) ($variation['id'] ?? 0),
                    'sku' => (string) ($variation['sku'] ?? ''),
                    'name' => (string) ($variation['name'] ?? 'Default'),
                    'price_inc_tax' => $price !== null ? (float) $price : null,
                    'qty_available' => (float) ($variation['total_qty_available'] ?? 0),
                    'locations' => [],
                ];
            })
            ->filter(fn ($variation) => $variation['variation_id'] > 0
                && $variation['qty_available'] >= 1)
            ->values()
            ->all();

        if (empty($variations)) {
            return null;
        }

        $brand = $product['brand'] ?? null;
        [$categoryName, $subCategoryName] = $this->categoryLabelParts($product);

        return [
            'id' => (int) ($product['id'] ?? 0),
            'name' => (string) ($product['name'] ?? ''),
            'description' => (string) ($product['description'] ?? ''),
            'warranties' => trim((string) ($product['warranties'] ?? '')),
            'warranty' => null,
            'image_url' => (string) ($product['image_url'] ?? ''),
            'brand' => is_array($brand) ? (string) ($brand['name'] ?? '') : (string) ($brand ?? ''),
            'category' => $categoryName,
            'sub_category' => $subCategoryName,
            'unit' => null,
            'source' => 'servo',
            'has_price' => collect($variations)->contains(fn ($variation) => $variation['price_inc_tax'] !== null),
            'variations' => $variations,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function categoryLabelParts(array $row, bool $fallbackToName = false): array
    {
        $categoryName = '';
        if (is_array($row['category'] ?? null)) {
            $categoryName = trim((string) ($row['category']['name'] ?? ''));
        }
        if ($categoryName === '') {
            $categoryName = trim((string) ($row['category_name'] ?? ''));
        }

        $subCategoryName = '';
        if (is_array($row['sub_category'] ?? null)) {
            $subCategoryName = trim((string) ($row['sub_category']['name'] ?? ''));
        }
        if ($subCategoryName === '') {
            $subCategoryName = trim((string) ($row['sub_category_name'] ?? ''));
        }

        if ($categoryName === '' && $subCategoryName === '' && $fallbackToName) {
            $categoryName = trim((string) ($row['name'] ?? ''));
        }

        return [$categoryName, $subCategoryName];
    }
}
