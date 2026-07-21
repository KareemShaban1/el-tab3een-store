<?php

namespace App\Utils;

use App\Services\Tab3eenCatalogService;
use App\Transaction;
use Illuminate\Validation\ValidationException;

class ServoOrderUtil
{
    /** @var array<string, array{product_name: string, variation_name: string, price: float|null}>|null */
    private ?array $catalogVariationMapCache = null;

    public function __construct(
        private Tab3eenCatalogService $catalogService
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    public function normalizeAndEnrichItems(array $products): array
    {
        $catalogMap = $this->buildCatalogVariationMap();

        return collect($products)->map(function ($product) use ($catalogMap) {
            $product_id = (int) ($product['product_id'] ?? $product['id'] ?? 0);
            $variation_id = (int) ($product['variation_id'] ?? 0);
            $quantity = (float) ($product['quantity'] ?? 0);

            if ($product_id <= 0 || $variation_id <= 0 || $quantity <= 0) {
                throw ValidationException::withMessages([
                    'products' => __('This product is no longer available for purchase.'),
                ]);
            }

            $name = trim((string) ($product['name'] ?? ''));
            $variation_name = trim((string) ($product['variation_name'] ?? ''));
            $price = isset($product['price']) ? (float) $product['price'] : null;
            $catalogKey = $product_id.'-'.$variation_id;

            if ($name === '' && isset($catalogMap[$catalogKey])) {
                $name = $catalogMap[$catalogKey]['product_name'];
                $variation_name = $variation_name !== '' ? $variation_name : $catalogMap[$catalogKey]['variation_name'];
                $price = $price ?? $catalogMap[$catalogKey]['price'];
            }

            return array_filter([
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'quantity' => $quantity,
                'name' => $name !== '' ? $name : null,
                'variation_name' => $variation_name !== '' ? $variation_name : null,
                'price' => $price,
            ], fn ($value) => $value !== null);
        })->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public function formatItems(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $catalogMap = $this->buildCatalogVariationMap();

        return collect($items)->map(function ($item) use ($catalogMap) {
            $product_id = (int) ($item['product_id'] ?? 0);
            $variation_id = (int) ($item['variation_id'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $catalogKey = $product_id.'-'.$variation_id;

            $product_name = trim((string) ($item['name'] ?? ''));
            $variation_name = trim((string) ($item['variation_name'] ?? ''));
            $unit_price = isset($item['price']) ? (float) $item['price'] : null;

            if ($product_name === '' && isset($catalogMap[$catalogKey])) {
                $product_name = $catalogMap[$catalogKey]['product_name'];
                $variation_name = $variation_name !== '' ? $variation_name : $catalogMap[$catalogKey]['variation_name'];
                $unit_price = $unit_price ?? $catalogMap[$catalogKey]['price'];
            }

            if ($product_name === '') {
                $product_name = __('storefront.orders.product_number', ['id' => $product_id]);
            }

            return [
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'quantity' => $quantity,
                'product_name' => $product_name,
                'variation_name' => $variation_name,
                'unit_price' => $unit_price,
                'line_total' => $unit_price !== null ? $quantity * $unit_price : null,
            ];
        })->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function formatLocalItems(?Transaction $transaction): array
    {
        if ($transaction === null) {
            return [];
        }

        if (! $transaction->relationLoaded('sell_lines')) {
            $transaction->load(['sell_lines.product', 'sell_lines.variations']);
        }

        return collect($transaction->sell_lines)->map(function ($line) {
            $quantity = (float) $line->quantity;
            $unit_price = (float) $line->unit_price_inc_tax;

            return [
                'product_id' => (int) $line->product_id,
                'variation_id' => (int) $line->variation_id,
                'product_name' => optional($line->product)->name
                    ?: __('storefront.orders.product_number', ['id' => $line->product_id]),
                'variation_name' => (string) (optional($line->variations)->name ?? ''),
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'line_total' => $quantity * $unit_price,
            ];
        })->values()->all();
    }

    /**
     * @return array<string, array{product_name: string, variation_name: string, price: float|null}>
     */
    private function buildCatalogVariationMap(): array
    {
        if ($this->catalogVariationMapCache !== null) {
            return $this->catalogVariationMapCache;
        }

        return $this->catalogVariationMapCache = $this->catalogService->getVariationLookupMap();
    }
}
