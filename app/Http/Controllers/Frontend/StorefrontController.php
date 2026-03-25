<?php

namespace App\Http\Controllers\Frontend;

use App\Business;
use App\Brands;
use App\Category;
use App\BusinessLocation;
use App\Product;
use App\Variation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StorefrontController extends Controller
{
    public function home(Request $request)
    {
        $business_id = $this->resolveBusinessId($request);
        $location_id = $this->resolveLocationId($business_id, $request);
        $products = Product::where('business_id', $business_id)
            ->active()
            ->productForSales()
            ->whereHas('variations.variation_location_details', function ($q) use ($location_id) {
                $q->where('location_id', $location_id)->where('qty_available', '>', 0);
            })
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

    public function products(Request $request)
    {
        $business_id = $this->resolveBusinessId($request);
        $location_id = $this->resolveLocationId($business_id, $request);

        $query = Product::where('products.business_id', $business_id)
            ->active()
            ->productForSales()
            ->whereHas('variations.variation_location_details', function ($q) use ($location_id) {
                $q->where('location_id', $location_id)->where('qty_available', '>', 0);
            })
            ->with([
                'brand:id,name',
                'category:id,name',
            ]);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->integer('brand_id'));
        }
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->input('q').'%');
        }

        $products = $query->paginate(20);

        $items = $products->getCollection()->map(function ($product) use ($location_id) {
            $variations = Variation::where('product_id', $product->id)
                ->with(['variation_location_details' => function ($q) use ($location_id) {
                    $q->where('location_id', $location_id);
                }])
                ->get();
            $in_stock_variations = $variations->filter(function ($v) {
                return (float) optional($v->variation_location_details->first())->qty_available > 0;
            })->values();
            $primary_variation = $in_stock_variations->first();

            $in_stock = $in_stock_variations->sum(function ($v) {
                return (float) optional($v->variation_location_details->first())->qty_available;
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
                'variations' => $in_stock_variations->map(function ($v) {
                    return [
                        'variation_id' => $v->id,
                        'name' => $v->name,
                        'sku' => $v->sub_sku,
                        'price_inc_tax' => (float) $v->sell_price_inc_tax,
                        'qty_available' => (float) optional($v->variation_location_details->first())->qty_available,
                    ];
                })->values(),
            ];
        })->filter(function ($item) {
            return ! empty($item['variation_id']) && (float) $item['in_stock_qty'] > 0;
        })->values();

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
            ],
        ];

        if (! $request->expectsJson()) {
            $categories = Category::where('business_id', $business_id)->select('id', 'name')->orderBy('name')->get();
            $brands = Brands::where('business_id', $business_id)->select('id', 'name')->orderBy('name')->get();

            return view('frontend.store.products')->with([
                'payload' => $payload,
                'categories' => $categories,
                'brands' => $brands,
            ]);
        }

        return response()->json($payload);
    }

    public function product(Request $request, int $id)
    {
        $business_id = $this->resolveBusinessId($request);
        $location_id = $this->resolveLocationId($business_id, $request);

        $product = Product::where('business_id', $business_id)
            ->active()
            ->productForSales()
            ->whereHas('variations.variation_location_details', function ($q) use ($location_id) {
                $q->where('location_id', $location_id)->where('qty_available', '>', 0);
            })
            ->with(['brand:id,name', 'category:id,name', 'unit:id,actual_name,short_name'])
            ->findOrFail($id);

        $location_records = BusinessLocation::where('business_id', $business_id)
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
            ->filter(function ($variation) use ($location_id) {
                return $variation->variation_location_details->contains(function ($row) use ($location_id) {
                    return (int) $row->location_id === (int) $location_id && (float) $row->qty_available > 0;
                });
            })
            ->map(function ($variation) use ($location_id, $location_records) {
                $sku = $variation->sub_sku;
                $name = $variation->name;
                $sku = is_scalar($sku) ? (string) $sku : '';
                $name = is_scalar($name) ? (string) $name : '';

                $preferred_row = $variation->variation_location_details->first(function ($row) use ($location_id) {
                    return (int) $row->location_id === (int) $location_id;
                });

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
        $unitShort = optional($product->unit)->short_name;

        $payload = [
            'success' => true,
            'business_id' => $business_id,
            'location_id' => $location_id,
            'data' => [
                'id' => $product->id,
                'name' => is_scalar($product->name) ? (string) $product->name : '',
                'image_url' => $product->image_url,
                'brand' => is_scalar($brandName) ? (string) $brandName : null,
                'category' => is_scalar($categoryName) ? (string) $categoryName : null,
                'unit' => is_scalar($unitShort) ? (string) $unitShort : null,
                'variations' => $variations,
            ],
        ];

        if (! $request->expectsJson()) {
            return view('frontend.store.product')->with([
                'payload' => $payload,
            ]);
        }

        return response()->json($payload);
    }

    public function categories(Request $request)
    {
        $business_id = $this->resolveBusinessId($request);

        $categories = Category::where('business_id', $business_id)
          //   ->whereNull('parent_id')
->where('category_type', 'product')
->where('deleted_at', null)
->where('parent_id', 0)
            ->select('id', 'name')
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(function ($category) use ($business_id) {
                $count = Product::where('business_id', $business_id)
                    ->active()
                    ->productForSales()
                    ->where('category_id', $category->id)
                    ->count();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'count' => $count,
                ];
            });


        return response()->json([
            'success' => true,
            'business_id' => $business_id,
            'data' => $categories,
        ]);
    }

    public function flashDeals(Request $request)
    {
        $business_id = $this->resolveBusinessId($request);
        $location_id = $this->resolveLocationId($business_id, $request);

        $products = Product::where('business_id', $business_id)
            ->active()
            ->productForSales()
            ->whereHas('variations.variation_location_details', function ($q) use ($location_id) {
                $q->where('location_id', $location_id)->where('qty_available', '>', 0);
            })
            ->select('id', 'name', 'image', 'brand_id')
            ->with(['brand:id,name'])
            ->limit(8)
            ->get();

        $deals = $products->map(function ($product) use ($location_id) {
            $variation = Variation::where('product_id', $product->id)
                ->with(['variation_location_details' => function ($q) use ($location_id) {
                    $q->where('location_id', $location_id);
                }])
                ->get()
                ->first(function ($v) {
                    return (float) optional($v->variation_location_details->first())->qty_available > 0;
                });

            $price = (float) optional($variation)->sell_price_inc_tax;
            $old_price = $price > 0 ? round($price * 1.12, 2) : null;
            $stock = (float) optional(optional($variation)->variation_location_details->first())->qty_available;
            $sold_pct = $stock <= 0 ? 100 : min(95, max(20, 100 - (int) $stock));

            return [
                'id' => $product->id,
                'variation_id' => optional($variation)->id,
                'name' => $product->name,
                'brand' => optional($product->brand)->name,
                'image_url' => $product->image_url,
                'price' => $price,
                'old_price' => $old_price,
                'sold_pct' => $sold_pct,
                'qty_left' => max(0, (int) round($stock)),
            ];
        })->filter(function ($item) {
            return ! empty($item['variation_id']) && $item['price'] > 0;
        })->values();

        return response()->json([
            'success' => true,
            'business_id' => $business_id,
            'location_id' => $location_id,
            'data' => $deals,
        ]);
    }

    private function resolveBusinessId(Request $request): int
    {

return 273;

//         if ($request->filled('business_id')) {
//             return (int) $request->input('business_id');
//         }

//         return (int) Business::query()->value('id');
    }

    private function resolveLocationId(int $business_id, Request $request): int
    {
        if ($request->filled('location_id')) {
            return (int) $request->input('location_id');
        }

        return (int) \App\BusinessLocation::where('business_id', $business_id)->value('id');
    }
}

