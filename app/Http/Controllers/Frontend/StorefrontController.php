<?php

namespace App\Http\Controllers\Frontend;

use App\Business;
use App\Brands;
use App\Category;
use App\BusinessLocation;
use App\Contact;
use App\Product;
use App\StoreHeroBanner;
use App\StorePage;
use App\Services\Tab3eenCatalogService;
use App\Utils\StorefrontSeoUtil;
use App\Variation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;

class StorefrontController extends Controller
{
    public function welcome(Request $request, Tab3eenCatalogService $tab3eenCatalogService)
    {
        $tab3eenCatalog = $tab3eenCatalogService->getCatalog();
        $business_id = self::resolveBusinessId($request);

        $localProductCount = Product::where('business_id', $business_id)
            ->active()
            ->productForSales()
            ->activeInApp()
            ->inStockByBusiness($business_id)
            ->count();

        $tab3eenProductCount = collect($tab3eenCatalog)->sum(
            fn ($category) => count($category['products'] ?? [])
        );

        $customerCount = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->where('contact_status', 'active')
            ->count();

        $brandCount = Brands::where('business_id', $business_id)->count();

        $heroBanners = StoreHeroBanner::forBusiness($business_id)
            ->active()
            ->ordered()
            ->get();

        if ($heroBanners->isEmpty()) {
            $heroBanners = collect([StoreHeroBanner::defaultFallback()]);
        }

        return view('frontend.welcome', [
            'tab3eenCatalog' => $tab3eenCatalog,
            'heroBanners' => $heroBanners,
            'heroStats' => [
                'products' => $this->formatHeroStat($localProductCount + $tab3eenProductCount),
                'customers' => $this->formatHeroStat($customerCount),
                'brands' => $this->formatHeroStat($brandCount),
            ],
        ]);
    }

    public function tab3eenCatalog(Request $request, Tab3eenCatalogService $tab3eenCatalogService)
    {
        $categoryId = $request->filled('category_id') ? $request->integer('category_id') : null;

        return response()->json([
            'success' => true,
            'data' => $tab3eenCatalogService->getCatalog($categoryId),
        ]);
    }

    public function tab3eenProduct(Request $request, int $id, Tab3eenCatalogService $tab3eenCatalogService)
    {
        $product = $tab3eenCatalogService->getProduct($id);

        if ($product === null) {
            abort(404);
        }

        $payload = [
            'success' => true,
            'data' => $product,
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        $seo = app(StorefrontSeoUtil::class)->forProduct(
            is_array($product) ? $product : (array) $product,
            $request->url()
        );

        return view('frontend.store.product', [
            'payload' => $payload,
            'isServoProduct' => true,
            'title' => $seo['meta_title'],
            'metaDescription' => $seo['meta_description'],
            'metaKeywords' => $seo['meta_keywords'],
            'seoOgImage' => $seo['og_image'],
            'seoCanonical' => $seo['canonical'],
        ]);
    }

    public function home(Request $request)
    {
        $business_id = self::resolveBusinessId($request);
        $location_id = $this->resolveLocationId($business_id, $request);
        $products = Product::where('business_id', $business_id)
            ->active()
            ->productForSales()
            ->activeInApp()
	  ->featured()
            ->whereHas('variations.variation_location_details', function ($q) use ($location_id) {
                $q->where('location_id', $location_id)->where('qty_available', '>', 0);
            })
            ->storefrontSortOrder()
            ->select('id', 'name', 'image', 'brand_id', 'category_id')
            ->with([
                'brand:id,name',
                'category:id,name',
                'variations' => function ($q) use ($location_id) {
                    $q->select('id', 'product_id', 'sub_sku', 'sell_price_inc_tax')
                        ->with(['variation_location_details' => function ($vd) use ($location_id) {
                            $vd->where('location_id', $location_id);
                        }]);
                },
            ])
            ->limit(24)
            ->get()
            ->map(function ($product) {
                $first_variation = $product->variations->first(function ($variation) {
                    return (float) optional($variation->variation_location_details->first())->qty_available > 0;
                });

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image_url' => $product->image_url,
                    'brand' => optional($product->brand)->name,
                    'category' => optional($product->category)->name,
                    'price' => optional($first_variation)->sell_price_inc_tax,
                ];
            });

        if (! $request->expectsJson()) {
            return view('frontend.store.index')->with([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'products' => $products,
            ]);
        }

        return response()->json([
            'success' => true,
            'business_id' => $business_id,
            'location_id' => $location_id,
            'products' => $products,
        ]);
    }

    public function products(Request $request, Tab3eenCatalogService $tab3eenCatalogService)
    {
        if ($request->input('source') === 'servo') {
            return $this->productsServoCatalog($request, $tab3eenCatalogService);
        }

        $business_id = self::resolveBusinessId($request);
        $location_id = $this->resolveLocationId($business_id, $request);
        $business_location_ids = BusinessLocation::where('business_id', $business_id)->where('is_active', 1)->pluck('id');
        [$selectedCategoryId, $selectedSubCategoryId] = $this->resolveStorefrontCategoryFilters($request, $business_id);

        $query = Product::where('products.business_id', $business_id)
            ->active()
            ->productForSales()
            ->activeInApp()
            // Only show products that have available stock in ANY location of this business.
            ->inStockByBusiness($business_id)
            ->storefrontSortOrder()
            ->with([
                'brand:id,name',
                'category:id,name',
            ]);

        if ($selectedSubCategoryId) {
            $query->where('sub_category_id', $selectedSubCategoryId);
            if ($selectedCategoryId) {
                $query->where('category_id', $selectedCategoryId);
            }
        } elseif ($selectedCategoryId) {
            $categoryId = $selectedCategoryId;
            // Parent categories are stored on products.category_id.
            // Subcategories are stored on products.sub_category_id.
            $query->where(function ($categoryQuery) use ($categoryId) {
                $categoryQuery->where('category_id', $categoryId)
                    ->orWhere('sub_category_id', $categoryId);
            });
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->integer('brand_id'));
        }
        if ($request->boolean('featured')) {
            $query->featured();
        }
        if ($request->filled('q')) {
            $query->whereNameOrTags(trim((string) $request->input('q')));
        }

        $priceMin = $request->filled('price_min') ? (float) $request->input('price_min') : null;
        $priceMax = $request->filled('price_max') ? (float) $request->input('price_max') : null;
        if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
            [$priceMin, $priceMax] = [$priceMax, $priceMin];
        }
        $this->restrictStorefrontProductsBySellPrice($query, $business_location_ids, $priceMin, $priceMax);

        $sort = (string) $request->input('sort', '');
        $this->applyStorefrontPriceSort($query, $sort, $business_location_ids);

        $products = $query->paginate(20);


        $items = $products->getCollection()->map(function ($product) use ($business_location_ids, $location_id) {
            $variations = Variation::where('product_id', $product->id)
                ->with(['variation_location_details' => function ($q) use ($business_location_ids) {
                    $q->whereIn('location_id', $business_location_ids)->where('qty_available', '>', 0);
                }])
                ->get();
            $in_stock_variations = $variations->filter(function ($v) {
                return (float) $v->variation_location_details->sum('qty_available') > 0;
            })->values();

            $primary_variation = $in_stock_variations->first(function ($v) use ($location_id) {
                return $v->variation_location_details->contains(function ($row) use ($location_id) {
                    return (int) $row->location_id === (int) $location_id && (float) $row->qty_available > 0;
                });
            }) ?? $in_stock_variations->first();

            $in_stock = $in_stock_variations->sum(function ($v) {
                return (float) $v->variation_location_details->sum('qty_available');
            });

            return [
                'id' => $product->id,
                'name' => $product->name,
                'image_url' => $product->image_url,
                'brand' => optional($product->brand)->name,
                'category' => optional($product->category)->name,
                'in_stock_qty' => $in_stock,
                'variation_id' => optional($primary_variation)->id,
                'min_price' => optional($in_stock_variations->sortBy('sell_price_inc_tax')->first())->sell_price_inc_tax,
                'max_price' => optional($in_stock_variations->sortByDesc('sell_price_inc_tax')->first())->sell_price_inc_tax,
                'variations' => $in_stock_variations->map(function ($v) use ($location_id) {
                    $locations = $v->variation_location_details
                        ->filter(function ($row) {
                            return (float) ($row->qty_available ?? 0) > 0;
                        })
                        ->map(function ($row) use ($location_id) {
                            $lid = (int) $row->location_id;

                            return [
                                'location_id' => $lid,
                                'qty_available' => (float) $row->qty_available,
                                'is_checkout_location' => $lid === (int) $location_id,
                            ];
                        })
                        ->sortByDesc(function ($locRow) {
                            return $locRow['is_checkout_location'] ? 1 : 0;
                        })
                        ->values()
                        ->all();

                    return [
                        'variation_id' => $v->id,
                        'name' => $v->name,
                        'sku' => $v->sub_sku,
                        'price_inc_tax' => (float) $v->sell_price_inc_tax,
                        'qty_available' => (float) $v->variation_location_details->sum('qty_available'),
                        'locations' => $locations,
                    ];
                })->values(),
            ];
        })
            ->filter(function ($item) {
                return (float) ($item['in_stock_qty'] ?? 0) > 0;
            })
            ->values();


        // Keep pagination metadata (current/last page, URLs, etc) but swap the collection
        // from Eloquent models to the lightweight arrays needed by the storefront view.
        $products->setCollection($items);
        $products->appends($request->query());
        $filterQuery = array_filter([
            'q' => $request->filled('q') ? (string) $request->input('q') : null,
            'category_id' => $selectedCategoryId ? (string) $selectedCategoryId : null,
            'sub_category_id' => $selectedSubCategoryId ? (string) $selectedSubCategoryId : null,
            'brand_id' => $request->filled('brand_id') ? (string) $request->input('brand_id') : null,
            'featured' => $request->boolean('featured') ? '1' : null,
            'price_min' => $request->filled('price_min') ? (string) $request->input('price_min') : null,
            'price_max' => $request->filled('price_max') ? (string) $request->input('price_max') : null,
            'sort' => in_array($sort, ['price_asc', 'price_desc'], true) ? $sort : null,
        ], function ($v) {
            return $v !== null && $v !== '';
        });
        $filterQueryForJson = empty($filterQuery) ? new \stdClass : $filterQuery;

        $payload = [
            'success' => true,
            'business_id' => $business_id,
            'location_id' => $location_id,
            'data' => $items,
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'filters' => $filterQueryForJson,
                'prev_page_url' => $products->previousPageUrl(),
                'next_page_url' => $products->nextPageUrl(),
            ],
        ];

        if (! $request->expectsJson()) {
            $categories = Category::where('business_id', $business_id)
                ->where('category_type', 'product')
                ->where('parent_id', 0)
                ->activeInApp()
                ->storefrontSortOrder()
                ->select('id', 'name')
                ->get();
            $subCategories = Category::where('business_id', $business_id)
                ->where('category_type', 'product')
                ->where('parent_id', '>', 0)
                ->activeInApp()
                ->storefrontSortOrder()
                ->select('id', 'name', 'parent_id')
                ->get();
            $brands = Brands::where('business_id', $business_id)->select('id', 'name')->orderBy('name')->get();

            $priceSlider = $this->getStorefrontCatalogPriceSliderSpec($business_id, $business_location_ids);

            return view('frontend.store.products')->with([
                'products' => $products,
                'categories' => $categories,
                'sub_categories' => $subCategories,
                'selected_category_id' => $selectedCategoryId,
                'selected_sub_category_id' => $selectedSubCategoryId,
                'brands' => $brands,
                'store_price_slider_min' => $priceSlider['min'],
                'store_price_slider_max' => $priceSlider['max'],
                'store_price_slider_step' => $priceSlider['step'],
                // Backwards compatibility for any other markup that might still read from `payload`.
                'payload' => $payload,
            ]);
        }

        $payload['pagination_html'] = (string) $products->links();

        return response()->json($payload);
    }

    public function product(Request $request, int $id)
    {
        $business_id = self::resolveBusinessId($request);
        $location_id = $this->resolveLocationId($business_id, $request);

        // Match catalog visibility: stock in any business location (see `products()` + `inStockByBusiness`).
        // Requiring stock only at `resolveLocationId()` caused 404 when default location differed from stocked locations.
        $product = Product::where('business_id', $business_id)
            ->active()
            ->productForSales()
            ->activeInApp()
            ->inStockByBusiness($business_id)
            ->with(['brand:id,name', 'category:id,name', 'sub_category:id,name', 'unit:id,actual_name,short_name', 'warranty'])
            ->findOrFail($id);

        $location_records = BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->select('id', 'name', 'landmark', 'city', 'state', 'country', 'zip_code', 'mobile')
            ->get()
            ->keyBy('id');
        $business_location_ids = $location_records->keys();

        $variations = Variation::where('product_id', $product->id)
            ->with([
                'variation_location_details' => function ($q) use ($business_location_ids) {
                    $q->whereIn('location_id', $business_location_ids)
                        ->where('qty_available', '>', 0)
                        ->orderBy('location_id');
                },
            ])
            ->get()
            ->filter(function ($variation) {
                return (float) $variation->variation_location_details->sum('qty_available') > 0;
            })
            ->map(function ($variation) use ($location_id, $location_records) {
                $sku = $variation->sub_sku;
                $name = $variation->name;
                $sku = is_scalar($sku) ? (string) $sku : '';
                $name = is_scalar($name) ? (string) $name : '';

                $preferred_row = $variation->variation_location_details->first(function ($row) use ($location_id) {
                    return (int) $row->location_id === (int) $location_id;
                }) ?? $variation->variation_location_details->first();

                $locations = $variation->variation_location_details
                    ->map(function ($row) use ($location_id, $location_records) {
                        $lid = (int) $row->location_id;
                        $loc = $location_records->get($lid)
                            ?? $location_records->get((string) $lid)
                            ?? $location_records->firstWhere('id', $lid);
                        $loc_name = $loc && is_scalar($loc->name) ? (string) $loc->name : '';

                        $parts = [];
                        if ($loc) {
                            foreach (['landmark', 'city', 'state', 'country'] as $field) {
                                $segment = $loc->{$field} ?? null;
                                if (is_scalar($segment) && trim((string) $segment) !== '') {
                                    $parts[] = trim((string) $segment);
                                }
                            }
                            $zip = $loc->zip_code ?? null;
                            if (is_scalar($zip) && trim((string) $zip) !== '') {
                                $parts[] = trim((string) $zip);
                            }
                        }
                        $address = implode(', ', $parts);
                        $mobile = ($loc && is_scalar($loc->mobile ?? null)) ? trim((string) $loc->mobile) : '';

                        return [
                            'location_id' => $lid,
                            'name' => $loc_name !== '' ? $loc_name : ('#'.$lid),
                            'address' => $address,
                            'mobile' => $mobile,
                            'qty_available' => (float) $row->qty_available,
                            'is_checkout_location' => (int) $lid === (int) $location_id,
                        ];
                    })
                    ->sortByDesc(function ($locRow) {
                        return $locRow['is_checkout_location'] ? 1 : 0;
                    })
                    ->values()
                    ->all();


                return [
                    'variation_id' => $variation->id,
                    'sku' => $sku,
                    'name' => $name,
                    'price_inc_tax' => $variation->sell_price_inc_tax,
                    'qty_available' => (float) optional($preferred_row)->qty_available,
                    'locations' => $locations,
                ];
            });

        $brandName = optional($product->brand)->name;
        $categoryName = optional($product->category)->name;
        $subCategoryName = optional($product->sub_category)->name;
        $unitShort = optional($product->unit)->short_name;
        $warranty = $product->warranty;

        $payload = [
            'success' => true,
            'business_id' => $business_id,
            'location_id' => $location_id,
            'data' => [
                'id' => $product->id,
                'name' => is_scalar($product->name) ? (string) $product->name : '',
                'description' => is_scalar($product->product_description) ? (string) $product->product_description : '',
                'warranties' => is_scalar($product->warranties ?? null) ? trim((string) $product->warranties) : '',
                'warranty' => $warranty ? [
                    'name' => is_scalar($warranty->name) ? (string) $warranty->name : '',
                    'description' => is_scalar($warranty->description ?? null) ? (string) $warranty->description : '',
                    'duration' => (int) $warranty->duration,
                    'duration_type' => is_scalar($warranty->duration_type) ? (string) $warranty->duration_type : '',
                    'display_name' => $warranty->display_name,
                ] : null,
                'image_url' => $product->image_url,
                'brand' => is_scalar($brandName) ? (string) $brandName : null,
                'category' => is_scalar($categoryName) ? (string) $categoryName : null,
                'sub_category' => is_scalar($subCategoryName) ? (string) $subCategoryName : null,
                'unit' => is_scalar($unitShort) ? (string) $unitShort : null,
                'variations' => $variations,
            ],
        ];

        if (! $request->expectsJson()) {
            $seo = app(StorefrontSeoUtil::class)->forProduct($product, $request->url());

            return view('frontend.store.product')->with([
                'payload' => $payload,
                'title' => $seo['meta_title'],
                'metaDescription' => $seo['meta_description'],
                'metaKeywords' => $seo['meta_keywords'],
                'seoOgImage' => $seo['og_image'],
                'seoCanonical' => $seo['canonical'],
            ]);
        }

        return response()->json($payload);
    }

    public function categories(Request $request)
    {
        $business_id = self::resolveBusinessId($request);

        $categories = Category::where('business_id', $business_id)
            ->where('category_type', 'product')
            ->where('parent_id', 0)
            ->activeInApp()
            ->storefrontSortOrder()
            ->select('id', 'name', 'order', 'featured')
            ->with(['media', 'sub_categories' => function ($query) {
                $query->where('category_type', 'product')
                    ->activeInApp()
                    ->storefrontSortOrder()
                    ->select('id', 'name', 'parent_id', 'order', 'featured')
                    ->with('media');
            }])
            ->limit(30)
            ->get()
            ->map(function ($category) use ($business_id) {
                $count = Product::where('business_id', $business_id)
                    ->active()
                    ->productForSales()
                    ->activeInApp()
                    ->where('category_id', $category->id)
                    ->count();

                $sub_categories = $category->sub_categories
                    ->sortBy([
                        ['order', 'asc'],
                        ['name', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->map(function ($sub) use ($business_id) {
                    $sub_count = Product::where('business_id', $business_id)
                        ->active()
                        ->productForSales()
                        ->activeInApp()
                        ->where(function ($categoryQuery) use ($sub) {
                            $categoryQuery->where('category_id', $sub->id)
                                ->orWhere('sub_category_id', $sub->id);
                        })
                        ->count();

                    return [
                        'id' => $sub->id,
                        'name' => $sub->name,
                        'parent_id' => $sub->parent_id,
                        'order' => (int) ($sub->order ?? 0),
                        'count' => $sub_count,
                        'image_url' => $sub->image_url,
                    ];
                })->values();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'order' => (int) ($category->order ?? 0),
                    'featured' => (int) ($category->featured ?? 0),
                    'count' => $count,
                    'image_url' => $category->image_url,
                    'sub_categories' => $sub_categories,
                ];
            })
            ->sortBy([
                ['order', 'asc'],
                ['featured', 'desc'],
                ['name', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        return response()->json([
            'success' => true,
            'business_id' => $business_id,
            'data' => $categories,
        ]);
    }

    public function flashDeals(Request $request)
    {
        $business_id = self::resolveBusinessId($request);
        $location_id = $this->resolveLocationId($business_id, $request);
        $resolver = app(\App\Services\Pricing\DiscountResolverService::class);

        $flashDiscounts = $resolver->getActiveFlashSales($business_id, $location_id);
        $deals = collect();

        foreach ($flashDiscounts as $discount) {
            $variationCandidates = collect();

            if ($discount->variations->isNotEmpty()) {
                $variationCandidates = $discount->variations;
            } elseif (! empty($discount->product_id) && ! empty($discount->apply_on_all_variations) && $discount->product) {
                $variationCandidates = $discount->product->variations;
            } elseif (! empty($discount->category_id) || ! empty($discount->brand_id)) {
                $q = Product::where('business_id', $business_id)
                    ->active()
                    ->productForSales()
                    ->activeInApp();
                if (! empty($discount->category_id)) {
                    $q->where('category_id', $discount->category_id);
                }
                if (! empty($discount->brand_id)) {
                    $q->where('brand_id', $discount->brand_id);
                }
                $variationCandidates = Variation::whereIn('product_id', $q->pluck('id'))->with('product')->limit(20)->get();
            }

            foreach ($variationCandidates as $variation) {
                $variation->loadMissing(['product.brand', 'variation_location_details' => function ($q) use ($location_id) {
                    $q->where('location_id', $location_id);
                }]);
                $product = $variation->product;
                if (empty($product)) {
                    continue;
                }
                $stock = (float) optional($variation->variation_location_details->first())->qty_available;
                if ($stock <= 0) {
                    continue;
                }

                $base = (float) $variation->sell_price_inc_tax;
                $priced = $resolver->getCatalogPrice([
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'variation_id' => $variation->id,
                    'product' => $product,
                    'channel' => 'ecommerce',
                    'base_price_inc_tax' => $base,
                    'quantity' => 1,
                ]);

                if (empty($priced['discount']) || (int) $priced['discount']->id !== (int) $discount->id) {
                    // still show if this flash is the target even if another higher priority won
                    $final = $resolver->applyDiscountToAmount($base, $discount->discount_type, (float) $discount->discount_amount);
                } else {
                    $final = $priced['final_price_inc_tax'];
                }

                $quota = $discount->flash_quota_qty;
                $sold = (float) $discount->flash_sold_qty;
                $qty_left = ! is_null($quota) ? max(0, (float) $quota - $sold) : (int) round($stock);
                $sold_pct = ! is_null($quota) && $quota > 0
                    ? min(100, (int) round(($sold / (float) $quota) * 100))
                    : min(95, max(5, 100 - (int) min(95, $stock)));

                $deals->push([
                    'id' => $product->id,
                    'variation_id' => $variation->id,
                    'discount_id' => $discount->id,
                    'name' => $discount->banner_title ?: $product->name,
                    'brand' => optional($product->brand)->name,
                    'image_url' => $product->image_url,
                    'price' => round($final, 2),
                    'old_price' => $final < $base ? round($base, 2) : null,
                    'sold_pct' => $sold_pct,
                    'qty_left' => (int) round($qty_left),
                    'ends_at' => optional($discount->ends_at)->toDateTimeString(),
                    'is_flash' => true,
                ]);

                if ($deals->count() >= 12) {
                    break 2;
                }
            }
        }

        return response()->json([
            'success' => true,
            'business_id' => $business_id,
            'location_id' => $location_id,
            'data' => $deals->values(),
        ]);
    }

    /**
     * Validate a storefront promo code against a cart payload.
     */
    public function validatePromoCode(Request $request)
    {
        $business_id = self::resolveBusinessId($request);
        $location_id = $this->resolveLocationId($business_id, $request);
        $customer = auth('customer')->user();
        $contact = null;
        if ($customer) {
            $contact = Contact::where('business_id', $business_id)
                ->where(function ($q) use ($customer) {
                    $q->where('id', $customer->id)->orWhere('mobile', $customer->mobile);
                })
                ->first();
        }

        $result = app(\App\Services\Pricing\PromoCodeService::class)->validate($request->input('code', ''), [
            'business_id' => $business_id,
            'location_id' => $location_id,
            'contact_id' => $contact->id ?? ($customer->id ?? null),
            'customer_group_id' => $contact->customer_group_id ?? ($customer->customer_group_id ?? null),
            'channel' => 'ecommerce',
            'cart_subtotal' => (float) $request->input('cart_subtotal', 0),
            'cart_lines' => $request->input('cart_lines', []),
        ]);

        if (! empty($result['success'])) {
            $result['msg'] = __('lang_v1.promo_code_applied');
            unset($result['promo_code'], $result['discount']);
        }

        return response()->json($result);
    }

    private function productsServoCatalog(Request $request, Tab3eenCatalogService $tab3eenCatalogService)
    {
        $business_id = self::resolveBusinessId($request);
        $location_id = $this->resolveLocationId($business_id, $request);
        $categoryId = $request->filled('category_id') ? $request->integer('category_id') : null;
        $subCategoryId = $request->filled('sub_category_id') ? $request->integer('sub_category_id') : null;
        $fullCatalog = $tab3eenCatalogService->getCatalog(null);
        $catalog = $fullCatalog;

        if ($categoryId !== null || $subCategoryId !== null) {
            $filterId = $categoryId ?: $subCategoryId;
            $catalog = collect($fullCatalog)
                ->filter(function ($category) use ($filterId) {
                    $aliasIds = array_map('intval', $category['alias_ids'] ?? []);

                    return (int) ($category['id'] ?? 0) === $filterId
                        || (int) ($category['category_id'] ?? 0) === $filterId
                        || in_array($filterId, $aliasIds, true);
                })
                ->values()
                ->all();
        }

        $servoCategoryName = '';
        if ($categoryId !== null || $subCategoryId !== null) {
            $matchedCategory = collect($catalog)->first();
            $servoCategoryName = $matchedCategory
                ? (string) ($matchedCategory['category_name'] ?? $matchedCategory['name'] ?? '')
                : '';
        }

        $items = collect($catalog)
            ->flatMap(function ($category) {
                return collect($category['products'] ?? [])
                    ->map(fn ($product) => $this->mapServoCatalogProductForStorefront($product, $category));
            })
            ->values();

        if ($subCategoryId) {
            $matchedSub = collect($fullCatalog)
                ->flatMap(fn ($category) => collect($category['sub_categories'] ?? [])->map(function ($sub) use ($category) {
                    $sub['parent_id'] = (int) ($category['id'] ?? 0);

                    return $sub;
                }))
                ->first(fn ($sub) => (int) ($sub['id'] ?? 0) === $subCategoryId);
            $productIds = collect(is_array($matchedSub) ? ($matchedSub['product_ids'] ?? []) : [])
                ->map(fn ($id) => (int) $id)
                ->all();
            if ($matchedSub) {
                $servoCategoryName = (string) ($matchedSub['name'] ?? $servoCategoryName);
            }
            $items = $items
                ->filter(fn ($item) => in_array((int) ($item['id'] ?? 0), $productIds, true))
                ->values();
        }

        if ($request->filled('q')) {
            $needle = mb_strtolower(trim((string) $request->input('q')));
            $items = $items->filter(function ($item) use ($needle) {
                $tagsHaystack = mb_strtolower(implode(' ', $item['tags'] ?? []));

                return str_contains(mb_strtolower((string) ($item['name'] ?? '')), $needle)
                    || str_contains(mb_strtolower((string) ($item['brand'] ?? '')), $needle)
                    || ($tagsHaystack !== '' && str_contains($tagsHaystack, $needle));
            })->values();
        }

        if ($request->filled('brand_id')) {
            $brandName = Brands::where('business_id', $business_id)
                ->where('id', $request->integer('brand_id'))
                ->value('name');
            if ($brandName) {
                $needle = mb_strtolower((string) $brandName);
                $items = $items->filter(function ($item) use ($needle) {
                    return str_contains(mb_strtolower((string) ($item['name'] ?? '')), $needle)
                        || str_contains(mb_strtolower((string) ($item['brand'] ?? '')), $needle);
                })->values();
            }
        }

        $priceMin = $request->filled('price_min') ? (float) $request->input('price_min') : null;
        $priceMax = $request->filled('price_max') ? (float) $request->input('price_max') : null;
        if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
            [$priceMin, $priceMax] = [$priceMax, $priceMin];
        }
        if ($priceMin !== null || $priceMax !== null) {
            $items = $items->filter(function ($item) use ($priceMin, $priceMax) {
                $price = (float) ($item['min_price'] ?? 0);
                if ($priceMin !== null && $price < $priceMin) {
                    return false;
                }
                if ($priceMax !== null && $price > $priceMax) {
                    return false;
                }

                return true;
            })->values();
        }

        $sort = (string) $request->input('sort', '');
        if ($sort === 'price_asc') {
            $items = $items->sortBy(fn ($item) => (float) ($item['min_price'] ?? 0))->values();
        } elseif ($sort === 'price_desc') {
            $items = $items->sortByDesc(fn ($item) => (float) ($item['min_price'] ?? 0))->values();
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;
        $total = $items->count();
        $pageItems = $items->slice(($page - 1) * $perPage, $perPage)->values();

        $products = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $filterQuery = array_filter([
            'source' => 'servo',
            'q' => $request->filled('q') ? (string) $request->input('q') : null,
            'category_id' => $request->filled('category_id') ? (string) $request->input('category_id') : null,
            'sub_category_id' => $request->filled('sub_category_id') ? (string) $request->input('sub_category_id') : null,
            'brand_id' => $request->filled('brand_id') ? (string) $request->input('brand_id') : null,
            'price_min' => $request->filled('price_min') ? (string) $request->input('price_min') : null,
            'price_max' => $request->filled('price_max') ? (string) $request->input('price_max') : null,
            'sort' => in_array($sort, ['price_asc', 'price_desc'], true) ? $sort : null,
        ], fn ($value) => $value !== null && $value !== '');
        $filterQueryForJson = empty($filterQuery) ? new \stdClass : $filterQuery;

        $payload = [
            'success' => true,
            'business_id' => $business_id,
            'location_id' => $location_id,
            'data' => $pageItems->all(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'filters' => $filterQueryForJson,
                'prev_page_url' => $products->previousPageUrl(),
                'next_page_url' => $products->nextPageUrl(),
            ],
        ];

        if (! $request->expectsJson()) {
            $categories = collect($fullCatalog)
                ->map(function ($category) {
                    $name = trim((string) ($category['category_name'] ?? ''));
                    if ($name === '') {
                        $name = trim((string) ($category['name'] ?? ''));
                    }

                    return (object) [
                        'id' => (int) ($category['id'] ?? 0),
                        'name' => $name,
                    ];
                })
                ->filter(fn ($category) => $category->id > 0 && $category->name !== '')
                ->values();

            $subCategories = collect($fullCatalog)
                ->flatMap(function ($category) {
                    $parentId = (int) ($category['id'] ?? 0);

                    return collect($category['sub_categories'] ?? [])->map(function ($sub) use ($parentId) {
                        return (object) [
                            'id' => (int) ($sub['id'] ?? 0),
                            'name' => trim((string) ($sub['name'] ?? '')),
                            'parent_id' => $parentId,
                        ];
                    });
                })
                ->filter(fn ($sub) => $sub->id > 0 && $sub->name !== '' && $sub->parent_id > 0)
                ->values();

            $brands = Brands::where('business_id', $business_id)->select('id', 'name')->orderBy('name')->get();
            $priceSliderSource = collect($catalog)
                ->flatMap(fn ($category) => $category['products'] ?? [])
                ->map(fn ($product) => $this->mapServoCatalogProductForStorefront($product, ['name' => '', 'category_name' => '', 'sub_category_name' => '']));
            $priceSlider = $this->getServoCatalogPriceSliderSpec($priceSliderSource);

            return view('frontend.store.products')->with([
                'products' => $products,
                'categories' => $categories,
                'sub_categories' => $subCategories,
                'selected_category_id' => $categoryId,
                'selected_sub_category_id' => $subCategoryId,
                'brands' => $brands,
                'store_price_slider_min' => $priceSlider['min'],
                'store_price_slider_max' => $priceSlider['max'],
                'store_price_slider_step' => $priceSlider['step'],
                'payload' => $payload,
                'isServoCatalog' => true,
                'servoCategoryName' => $servoCategoryName,
            ]);
        }

        $payload['pagination_html'] = (string) $products->links();

        return response()->json($payload);
    }

    /**
     * @param  array<string, mixed>  $product
     * @param  array<string, mixed>  $category
     * @return array<string, mixed>
     */
    private function mapServoCatalogProductForStorefront(array $product, array $category): array
    {
        $variations = collect($product['variations'] ?? [])
            ->map(function ($variation) {
                return [
                    'variation_id' => (int) ($variation['variation_id'] ?? 0),
                    'name' => (string) ($variation['name'] ?? 'Default'),
                    'sku' => (string) ($variation['sku'] ?? ''),
                    'price_inc_tax' => (float) ($variation['price'] ?? 0),
                    'qty_available' => (float) ($variation['qty_available'] ?? 0),
                ];
            })
            ->values()
            ->all();

        $defaultVariation = $variations[0] ?? null;
        $categoryName = trim((string) ($category['category_name'] ?? ''));
        $subCategoryName = trim((string) ($category['sub_category_name'] ?? ''));
        $categoryLabel = $categoryName !== ''
            ? $categoryName
            : ($subCategoryName !== '' ? $subCategoryName : (string) ($category['name'] ?? ''));

        return [
            'id' => (int) ($product['id'] ?? 0),
            'name' => (string) ($product['name'] ?? ''),
            'image_url' => (string) ($product['image_url'] ?? ''),
            'brand' => $categoryLabel,
            'category' => $categoryLabel,
            'tags' => array_values($product['tags'] ?? []),
            'source' => 'servo',
            'in_stock_qty' => collect($variations)->sum('qty_available'),
            'variation_id' => $defaultVariation ? (int) $defaultVariation['variation_id'] : 0,
            'min_price' => $defaultVariation ? (float) $defaultVariation['price_inc_tax'] : null,
            'max_price' => collect($variations)->max('price_inc_tax'),
            'variations' => $variations,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $items
     * @return array{min: float, max: float, step: float}
     */
    private function getServoCatalogPriceSliderSpec($items): array
    {
        $prices = $items
            ->flatMap(fn ($item) => collect($item['variations'] ?? [])->pluck('price_inc_tax'))
            ->filter(fn ($price) => $price !== null && (float) $price > 0)
            ->map(fn ($price) => (float) $price);

        if ($prices->isEmpty()) {
            return ['min' => 0.0, 'max' => 100000.0, 'step' => 1.0];
        }

        $min = floor($prices->min());
        $max = ceil($prices->max());
        if ($max <= $min) {
            $max = $min + 1;
        }

        return ['min' => $min, 'max' => $max, 'step' => 1.0];
    }

    /**
     * Min / max sell price (inc tax) among in-stock variations for active-in-app products — used for the storefront price slider.
     *
     * @return array{min: float, max: float, step: float}
     */
    private function getStorefrontCatalogPriceSliderSpec(int $business_id, $business_location_ids): array
    {
        if ($business_location_ids->isEmpty()) {
            return ['min' => 0.0, 'max' => 100000.0, 'step' => 1.0];
        }

        $row = Variation::query()
            ->whereHas('product', function ($q) use ($business_id) {
                $q->where('business_id', $business_id)
                    ->where('is_inactive', 0)
                    ->where('not_for_selling', 0)
                    ->where('active_in_app', 1);
            })
            ->whereHas('variation_location_details', function ($vd) use ($business_location_ids) {
                $vd->whereIn('location_id', $business_location_ids)
                    ->where('qty_available', '>', 0);
            })
            ->selectRaw('MIN(sell_price_inc_tax) as mn, MAX(sell_price_inc_tax) as mx')
            ->first();

        $min = $row && $row->mn !== null ? (float) $row->mn : 0.0;
        $max = $row && $row->mx !== null ? (float) $row->mx : 0.0;
        if ($max < $min) {
            $max = $min;
        }
        if ($max <= $min) {
            $max = $min + 1;
        }
        $span = $max - $min;
        $step = $span >= 1000 ? max(1.0, round($span / 500)) : ($span >= 100 ? 0.5 : 0.01);

        return ['min' => $min, 'max' => $max, 'step' => (float) $step];
    }

    /**
     * Keep products that have at least one in-stock variation whose sell price is inside the range.
     */
    private function restrictStorefrontProductsBySellPrice($query, $business_location_ids, ?float $minPrice, ?float $maxPrice): void
    {
        if ($minPrice === null && $maxPrice === null) {
            return;
        }

        if ($business_location_ids->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('variations', function ($vq) use ($business_location_ids, $minPrice, $maxPrice) {
            if ($minPrice !== null) {
                $vq->where('sell_price_inc_tax', '>=', $minPrice);
            }
            if ($maxPrice !== null) {
                $vq->where('sell_price_inc_tax', '<=', $maxPrice);
            }
            $vq->whereHas('variation_location_details', function ($vd) use ($business_location_ids) {
                $vd->whereIn('location_id', $business_location_ids)
                    ->where('qty_available', '>', 0);
            });
        });
    }

    /**
     * Sort storefront product listing by lowest / highest in-stock sell price.
     */
    private function applyStorefrontPriceSort($query, string $sort, $business_location_ids): void
    {
        if (! in_array($sort, ['price_asc', 'price_desc'], true)) {
            return;
        }

        if ($business_location_ids->isEmpty()) {
            return;
        }

        $direction = $sort === 'price_asc' ? 'asc' : 'desc';
        $placeholders = $business_location_ids->map(fn () => '?')->implode(',');

        $query->reorder()
            ->orderByRaw(
                "(SELECT MIN(variations.sell_price_inc_tax)
                    FROM variations
                    INNER JOIN variation_location_details
                        ON variation_location_details.variation_id = variations.id
                    WHERE variations.product_id = products.id
                        AND variation_location_details.location_id IN ({$placeholders})
                        AND variation_location_details.qty_available > 0
                ) {$direction}",
                $business_location_ids->all()
            );
    }

    /**
     * Resolved business context for the storefront (shared with header search / suggestions).
     */
    public static function resolveBusinessId(Request $request): int
    {
        return 273;

        //         if ($request->filled('business_id')) {
        //             return (int) $request->input('business_id');
        //         }

        //         return (int) Business::query()->value('id');
    }

    public function search(Request $request)
    {
        return view('frontend.store.search', [
            'initialQuery' => trim((string) $request->input('q', '')),
        ]);
    }

    public function page(Request $request, string $slug)
    {
        $business_id = self::resolveBusinessId($request);

        $page = StorePage::forBusiness($business_id)
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('frontend.store.page', [
            'page' => $page,
            'title' => $page->meta_title ?: $page->title,
            'metaDescription' => $page->meta_description ?: $page->excerpt,
            'seoCanonical' => $request->url(),
        ]);
    }

    /**
     * JSON autocomplete: active-in-app categories + local catalog products + Tab3een API products (name/tags).
     */
    public function searchSuggest(Request $request, Tab3eenCatalogService $tab3eenCatalogService)
    {
        $business_id = self::resolveBusinessId($request);

        $term = trim((string) $request->input('q', ''));
        if (mb_strlen($term) < 3) {
            return response()->json(['success' => true, 'results' => []]);
        }
        $term = mb_substr($term, 0, 100);
        $escaped = addcslashes($term, '%_\\');
        $like = '%'.$escaped.'%';

        $categoryQuery = Category::query()
            ->where('business_id', $business_id)
            ->where('category_type', 'product')
            ->activeInApp()
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(10)
            ->select('id', 'name');

        $productQuery = Product::query()
            ->where('business_id', $business_id)
            ->active()
            ->productForSales()
            ->activeInApp()
            ->inStockByBusiness($business_id)
            ->whereNameOrTags($term)
            ->orderBy('name')
            ->limit(10)
            ->select('id', 'name');

        $categories = $categoryQuery->get();
        $products = $productQuery->get();
        $results = [];

        foreach ($categories as $c) {
            $results[] = [
                'type' => 'category',
                'id' => $c->id,
                'name' => $c->name,
                'url' => route('store.products.index', ['category_id' => $c->id]),
            ];
        }

        foreach ($products as $p) {
            $results[] = [
                'type' => 'product',
                'id' => $p->id,
                'name' => $p->name,
                'url' => route('store.products.show', ['id' => $p->id]),
            ];
        }

        foreach ($tab3eenCatalogService->searchProducts($term, 10) as $p) {
            $results[] = [
                'type' => 'product',
                'id' => $p['id'],
                'name' => $p['name'],
                'source' => 'servo',
                'url' => route('store.tab3een.products.show', ['id' => $p['id']]),
            ];
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    private function resolveLocationId(int $business_id, Request $request): int
    {
        if ($request->filled('location_id')) {
            return (int) $request->input('location_id');
        }

        return (int) \App\BusinessLocation::where('business_id', $business_id)->where('is_active', 1)->value('id');
    }

    private function formatHeroStat(int $value): string
    {
        if ($value >= 1_000_000) {
            $scaled = $value / 1_000_000;
            $formatted = fmod($scaled, 1.0) === 0.0
                ? (string) (int) $scaled
                : rtrim(rtrim(number_format($scaled, 1, '.', ''), '0'), '.');

            return '+'.$formatted.'M';
        }

        if ($value >= 1_000) {
            $scaled = $value / 1_000;
            $formatted = fmod($scaled, 1.0) === 0.0
                ? (string) (int) $scaled
                : rtrim(rtrim(number_format($scaled, 1, '.', ''), '0'), '.');

            return '+'.$formatted.'K';
        }

        return '+'.number_format($value);
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function resolveStorefrontCategoryFilters(Request $request, int $businessId): array
    {
        $categoryId = $request->filled('category_id') ? $request->integer('category_id') : null;
        $subCategoryId = $request->filled('sub_category_id') ? $request->integer('sub_category_id') : null;

        if ($subCategoryId) {
            $subCategory = Category::query()
                ->where('business_id', $businessId)
                ->where('category_type', 'product')
                ->where('id', $subCategoryId)
                ->first();

            if ($subCategory && (int) $subCategory->parent_id > 0) {
                $categoryId = $categoryId ?: (int) $subCategory->parent_id;
            } else {
                $subCategoryId = null;
            }
        } elseif ($categoryId) {
            $category = Category::query()
                ->where('business_id', $businessId)
                ->where('category_type', 'product')
                ->where('id', $categoryId)
                ->first();

            if ($category && (int) $category->parent_id > 0) {
                $subCategoryId = (int) $category->id;
                $categoryId = (int) $category->parent_id;
            }
        }

        return [$categoryId, $subCategoryId];
    }
}