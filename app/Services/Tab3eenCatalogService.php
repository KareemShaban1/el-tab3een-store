<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Tab3eenCatalogService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCatalog(): array
    {
        $url = trim((string) config('storefront.tab3een_catalog_api_url', ''));

        if ($url === '') {
            return [];
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get($url);

            if (! $response->successful()) {
                Log::warning('Tab3een catalog API request failed.', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $body = $response->json();
            if (($body['status'] ?? null) !== 'success' || ! is_array($body['data'] ?? null)) {
                return [];
            }

            return $this->normalizeCategories($body['data']);
        } catch (\Throwable $e) {
            Log::warning('Tab3een catalog API exception: '.$e->getMessage(), [
                'url' => $url,
            ]);

            return [];
        }
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
                                && $variation['qty_available'] >= 1)
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
                    ->filter(fn ($product) => $product['id'] > 0 && ! empty($product['variations']))
                    ->values()
                    ->all();

                return [
                    'id' => (int) ($category['id'] ?? 0),
                    'name' => (string) ($category['name'] ?? ''),
                    'image' => (string) ($category['image'] ?? ''),
                    'sort_order' => (int) ($category['sort_order'] ?? 0),
                    'products' => $products,
                ];
            })
            ->filter(fn ($category) => ! empty($category['products']))
            ->sortBy('sort_order')
            ->values()
            ->all();

        return $normalized;
    }
}
