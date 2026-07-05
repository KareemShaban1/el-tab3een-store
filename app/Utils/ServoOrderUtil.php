<?php

namespace App\Utils;

use App\Services\Tab3eenCatalogService;
use App\Transaction;
use App\Variation;

class ServoOrderUtil
{
    /** @var array<string, array{product_name: string, variation_name: string, price: float|null}>|null */
    private ?array $catalogVariationMapCache = null;

    public function __construct(
        private Tab3eenCatalogService $catalogService
    ) {}

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
        $variationIds = collect($items)->pluck('variation_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        $localVariations = empty($variationIds)
            ? collect()
            : Variation::whereIn('id', $variationIds)
                ->with(['product', 'product_variation'])
                ->get()
                ->keyBy('id');

        return collect($items)->map(function ($item) use ($catalogMap, $localVariations) {
            $product_id = (int) ($item['product_id'] ?? 0);
            $variation_id = (int) ($item['variation_id'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $catalogKey = $product_id.'-'.$variation_id;

            $product_name = (string) ($item['name'] ?? '');
            $variation_name = (string) ($item['variation_name'] ?? '');
            $unit_price = isset($item['price']) ? (float) $item['price'] : null;

            if ($product_name === '' && isset($catalogMap[$catalogKey])) {
                $product_name = $catalogMap[$catalogKey]['product_name'];
                $variation_name = $catalogMap[$catalogKey]['variation_name'];
                $unit_price = $unit_price ?? $catalogMap[$catalogKey]['price'];
            }

            if ($product_name === '' && $localVariations->has($variation_id)) {
                $variation = $localVariations->get($variation_id);
                $product_name = optional($variation->product)->name ?: '';
                $variation_name = $variation->name ?: $variation_name;
                $unit_price = $unit_price ?? (float) ($variation->sell_price_inc_tax ?? 0);
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

        $map = [];

        foreach ($this->catalogService->getCatalog() as $category) {
            foreach ($category['products'] ?? [] as $product) {
                foreach ($product['variations'] ?? [] as $variation) {
                    $productId = (int) ($product['id'] ?? 0);
                    $variationId = (int) ($variation['variation_id'] ?? 0);
                    if ($productId <= 0 || $variationId <= 0) {
                        continue;
                    }

                    $map[$productId.'-'.$variationId] = [
                        'product_name' => (string) ($product['name'] ?? ''),
                        'variation_name' => (string) ($variation['name'] ?? ''),
                        'price' => isset($variation['price']) ? (float) $variation['price'] : null,
                    ];
                }
            }
        }

        return $this->catalogVariationMapCache = $map;
    }
}
