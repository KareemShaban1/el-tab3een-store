<?php

namespace App\Services\Pricing;

use App\Category;
use App\Discount;
use App\DiscountRedemption;
use App\Product;
use App\Utils\Util;
use App\Variation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DiscountResolverService
{
    protected $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Resolve the best automatic discount for a variation (skips promo-code-gated rules).
     *
     * @param  array  $context
     * @return \App\Discount|null
     */
    public function resolveForVariation(array $context)
    {
        $business_id = $context['business_id'] ?? null;
        $location_id = $context['location_id'] ?? null;
        $variation_id = $context['variation_id'] ?? null;
        $channel = $context['channel'] ?? 'pos';
        $customer_group_id = $context['customer_group_id'] ?? null;
        $price_group = $context['selling_price_group_id'] ?? ($context['price_group'] ?? null);
        $quantity = (float) ($context['quantity'] ?? 1);

        if (empty($business_id) || empty($variation_id)) {
            return null;
        }

        $product = $context['product'] ?? null;
        if (empty($product)) {
            $variation = Variation::with('product')->find($variation_id);
            if (empty($variation) || empty($variation->product)) {
                return null;
            }
            $product = $variation->product;
        }

        $now = Carbon::now()->toDateTimeString();

        $query = Discount::where('business_id', $business_id)
            ->where('is_active', 1)
            ->where('requires_promo_code', 0)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->where(function ($q) use ($location_id) {
                $q->whereNull('location_id');
                if (! empty($location_id)) {
                    $q->orWhere('location_id', $location_id);
                }
            });

        if ($channel === 'ecommerce') {
            $query->where('apply_in_ecommerce', 1);
        } else {
            $query->where('apply_in_pos', 1);
        }

        $this->applyCustomerGroupFilter($query, $customer_group_id);
        $this->applySpgFilter($query, $price_group);
        $this->applyTargetFilter($query, $product, $variation_id);

        $candidates = $query->orderBy('priority', 'desc')->latest()->get();

        foreach ($candidates as $discount) {
            if (! $this->passesQuotaAndLimits($discount, $customer_group_id, $quantity)) {
                continue;
            }

            $discount->formated_starts_at = $this->commonUtil->format_date($discount->starts_at->toDateTimeString(), true);
            $discount->formated_ends_at = $this->commonUtil->format_date($discount->ends_at->toDateTimeString(), true);

            return $discount;
        }

        return null;
    }

    /**
     * Catalog helper: base + discounted price for a variation.
     *
     * @return array{base_price_inc_tax: float, final_price_inc_tax: float, discount: ?Discount, old_price: ?float, is_flash: bool}
     */
    public function getCatalogPrice(array $context): array
    {
        $base = (float) ($context['base_price_inc_tax'] ?? 0);
        $discount = $this->resolveForVariation($context);

        $final = $base;
        if (! empty($discount) && $base > 0) {
            $final = $this->applyDiscountToAmount($base, $discount->discount_type, (float) $discount->discount_amount);
            if (! empty($discount->max_discount_amount) && $discount->discount_type === 'percentage') {
                $saved = $base - $final;
                if ($saved > (float) $discount->max_discount_amount) {
                    $final = $base - (float) $discount->max_discount_amount;
                }
            }
        }

        return [
            'base_price_inc_tax' => $base,
            'final_price_inc_tax' => max(0, $final),
            'discount' => $discount,
            'discount_id' => $discount->id ?? null,
            'discount_label' => $discount->name ?? null,
            'old_price' => (! empty($discount) && $final < $base) ? $base : null,
            'ends_at' => $discount->ends_at ?? null,
            'is_flash' => ! empty($discount) && ($discount->discount_kind === 'flash_sale'),
        ];
    }

    /**
     * Active flash-sale discounts for storefront listing.
     */
    public function getActiveFlashSales(int $business_id, ?int $location_id = null): Collection
    {
        $now = Carbon::now()->toDateTimeString();

        return Discount::where('business_id', $business_id)
            ->where('is_active', 1)
            ->where('discount_kind', 'flash_sale')
            ->where('requires_promo_code', 0)
            ->where('apply_in_ecommerce', 1)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->where(function ($q) use ($location_id) {
                $q->whereNull('location_id');
                if (! empty($location_id)) {
                    $q->orWhere('location_id', $location_id);
                }
            })
            ->where(function ($q) {
                $q->whereNull('flash_quota_qty')
                    ->orWhereColumn('flash_sold_qty', '<', 'flash_quota_qty');
            })
            ->with(['variations', 'product.variations', 'category'])
            ->orderBy('sort_order')
            ->orderBy('priority', 'desc')
            ->get();
    }

    public function applyDiscountToAmount(float $amount, ?string $type, float $discount_amount): float
    {
        if ($discount_amount <= 0) {
            return $amount;
        }

        if ($type === 'fixed') {
            return max(0, $amount - $discount_amount);
        }

        return max(0, $amount - (($discount_amount / 100) * $amount));
    }

    public function computeLineSavings(float $unit_price_before, ?string $type, float $discount_amount, float $qty = 1): float
    {
        $after = $this->applyDiscountToAmount($unit_price_before, $type, $discount_amount);

        return max(0, ($unit_price_before - $after) * $qty);
    }

    protected function applyCustomerGroupFilter($query, $customer_group_id): void
    {
        $query->where(function ($q) use ($customer_group_id) {
            $q->where('cg_mode', 'any')
                ->orWhere(function ($q2) {
                    // legacy / unset
                    $q2->whereNull('cg_mode')->where(function ($q3) {
                        $q3->where('applicable_in_cg', 0)->orWhereNull('applicable_in_cg');
                    });
                });

            if (! empty($customer_group_id)) {
                $q->orWhere('cg_mode', 'with_group_only')
                    ->orWhere(function ($q2) use ($customer_group_id) {
                        $q2->where('cg_mode', 'specific_groups')
                            ->whereHas('customerGroups', function ($cg) use ($customer_group_id) {
                                $cg->where('customer_group_id', $customer_group_id);
                            });
                    })
                    ->orWhere(function ($q2) use ($customer_group_id) {
                        $q2->where('cg_mode', 'exclude_groups')
                            ->whereDoesntHave('customerGroups', function ($cg) use ($customer_group_id) {
                                $cg->where('customer_group_id', $customer_group_id);
                            });
                    })
                    // legacy boolean when customer has a group
                    ->orWhere(function ($q2) {
                        $q2->whereNull('cg_mode')->where('applicable_in_cg', 1);
                    });
            } else {
                $q->orWhere(function ($q2) {
                    $q2->where('cg_mode', 'exclude_groups');
                });
            }
        });
    }

    protected function applySpgFilter($query, $price_group): void
    {
        if (! is_null($price_group) && $price_group !== '') {
            $query->where(function ($q) use ($price_group) {
                $q->whereNull('spg')
                    ->orWhere('spg', (string) $price_group);
            });
        } else {
            $query->whereNull('spg');
        }
    }

    protected function applyTargetFilter($query, $product, $variation_id): void
    {
        $brand_id = $product->brand_id ?? null;
        $category_id = $product->category_id ?? null;
        $sub_category_id = $product->sub_category_id ?? null;
        $product_id = $product->id ?? null;

        $parent_category_ids = [];
        if (! empty($sub_category_id)) {
            $parent_category_ids[] = $category_id;
        }

        $query->where(function ($q) use ($product, $variation_id, $brand_id, $category_id, $sub_category_id, $product_id) {
            // Variation-specific
            if (! empty($variation_id)) {
                $q->orWhereHas('variations', function ($sub) use ($variation_id) {
                    $sub->where('variation_id', $variation_id);
                });
            }

            // Product all variations
            if (! empty($product_id)) {
                $q->orWhere(function ($sub) use ($product_id) {
                    $sub->where('product_id', $product_id)
                        ->where('apply_on_all_variations', 1);
                });
            }

            // Category / brand (no variation pivot / not product-all)
            $q->orWhere(function ($sub) use ($brand_id, $category_id, $sub_category_id) {
                $sub->where(function ($inner) {
                    $inner->whereNull('product_id')->orWhere('product_id', 0);
                })
                ->whereDoesntHave('variations')
                ->where(function ($target) use ($brand_id, $category_id, $sub_category_id) {
                    if (! empty($brand_id) && ! empty($category_id)) {
                        $target->where(function ($t) use ($brand_id, $category_id) {
                            $t->where('brand_id', $brand_id)->where('category_id', $category_id);
                        });
                    }
                    if (! empty($brand_id)) {
                        $target->orWhere(function ($t) use ($brand_id) {
                            $t->where('brand_id', $brand_id)->whereNull('category_id');
                        });
                    }
                    if (! empty($category_id)) {
                        $target->orWhere(function ($t) use ($category_id) {
                            $t->where('category_id', $category_id)->whereNull('brand_id');
                        });
                        // include_sub_categories: discount on parent matches product sub-category parent
                        $target->orWhere(function ($t) use ($category_id) {
                            $t->where('include_sub_categories', 1)
                                ->where('category_id', $category_id);
                        });
                    }
                    if (! empty($sub_category_id)) {
                        $target->orWhere('sub_category_id', $sub_category_id);
                        // Parent category discount with include_sub_categories
                        $parent = Category::find($sub_category_id);
                        // also match discount.category_id = product.category_id with include flag
                        $target->orWhere(function ($t) use ($category_id) {
                            if (! empty($category_id)) {
                                $t->where('category_id', $category_id)
                                    ->where('include_sub_categories', 1);
                            }
                        });
                    }
                });
            });
        });
    }

    protected function passesQuotaAndLimits(Discount $discount, $customer_group_id, float $quantity): bool
    {
        if (! empty($discount->flash_quota_qty) && (float) $discount->flash_sold_qty + $quantity > (float) $discount->flash_quota_qty) {
            return false;
        }

        if (! empty($discount->max_qty_per_order) && $quantity > (float) $discount->max_qty_per_order) {
            return false;
        }

        if (! empty($discount->max_redemptions_total)) {
            $count = DiscountRedemption::where('discount_id', $discount->id)->count();
            if ($count >= (int) $discount->max_redemptions_total) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether a product/variation matches a discount target (for promo eligible_lines).
     */
    public function variationMatchesDiscount(Discount $discount, Product $product, int $variation_id): bool
    {
        if ($discount->variations()->where('variation_id', $variation_id)->exists()) {
            return true;
        }

        if (! empty($discount->product_id) && (int) $discount->product_id === (int) $product->id && ! empty($discount->apply_on_all_variations)) {
            return true;
        }

        // If discount is variation/product targeted only, stop
        if ($discount->variations()->exists() || (! empty($discount->product_id) && empty($discount->apply_on_all_variations))) {
            return false;
        }

        if (! empty($discount->sub_category_id) && (int) $discount->sub_category_id === (int) $product->sub_category_id) {
            return $this->brandMatches($discount, $product);
        }

        if (! empty($discount->category_id)) {
            $catMatch = (int) $discount->category_id === (int) $product->category_id
                || (! empty($discount->include_sub_categories) && (int) $discount->category_id === (int) $product->category_id)
                || (! empty($discount->include_sub_categories) && (int) $product->sub_category_id && $this->isChildCategory((int) $product->sub_category_id, (int) $discount->category_id));

            if ($catMatch) {
                return $this->brandMatches($discount, $product);
            }

            return false;
        }

        if (! empty($discount->brand_id)) {
            return (int) $discount->brand_id === (int) $product->brand_id;
        }

        // cart_wide style discount with no product targets → all lines eligible
        if (empty($discount->brand_id) && empty($discount->category_id) && empty($discount->product_id) && ! $discount->variations()->exists()) {
            return true;
        }

        return false;
    }

    protected function brandMatches(Discount $discount, Product $product): bool
    {
        if (empty($discount->brand_id)) {
            return true;
        }

        return (int) $discount->brand_id === (int) $product->brand_id;
    }

    protected function isChildCategory(int $child_id, int $parent_id): bool
    {
        $child = Category::find($child_id);

        return $child && (int) $child->parent_id === $parent_id;
    }
}
