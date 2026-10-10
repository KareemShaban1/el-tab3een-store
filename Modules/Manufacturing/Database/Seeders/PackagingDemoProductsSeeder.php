<?php

namespace Modules\Manufacturing\Database\Seeders;

use App\Business;
use App\BusinessLocation;
use App\Product;
use App\Unit;
use App\User;
use App\Variation;
use App\VariationLocationDetails;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds Step 1 products from docs/Feature_In_Arabic.md (caramel packaging demo).
 *
 * Usage:
 *   php artisan db:seed --class=Modules\\Manufacturing\\Database\\Seeders\\PackagingDemoProductsSeeder
 *
 * Optional .env:
 *   MFG_PACKAGING_SEED_BUSINESS_ID=273
 *   MFG_PACKAGING_SEED_LOCATION_ID=   (defaults to first location of business)
 */
class PackagingDemoProductsSeeder extends Seeder
{
    public function run()
    {
        if (! Schema::hasColumn('products', 'product_usage_type')) {
            $this->command?->error('Column products.product_usage_type missing. Run Manufacturing migrations first.');
            return;
        }

        $business_id = (int) env('MFG_PACKAGING_SEED_BUSINESS_ID', 0);
        if ($business_id <= 0) {
            $business = Business::orderBy('id')->first();
            $business_id = $business?->id ?? 0;
        }

        $business = Business::find($business_id);
        if (empty($business)) {
            $this->command?->error('Business not found. Set MFG_PACKAGING_SEED_BUSINESS_ID in .env');
            return;
        }

        $location_id = (int) env('MFG_PACKAGING_SEED_LOCATION_ID', 0);
        if ($location_id <= 0) {
            $location_id = BusinessLocation::where('business_id', $business_id)->value('id');
        }

        if (empty($location_id)) {
            $this->command?->error('No business location found for business #' . $business_id);
            return;
        }

        $created_by = User::where('business_id', $business_id)->value('id')
            ?? User::where('id', $business->owner_id)->value('id')
            ?? 1;

        $unit_kg = $this->ensureUnit($business_id, 'كجم', 'كجم', $created_by, true);
        $unit_liter = $this->ensureUnit($business_id, 'لتر', 'لتر', $created_by, true);
        $unit_pcs = $this->ensureUnit($business_id, 'قطعة', 'قطعة', $created_by, false);
        $unit_carton = $this->ensureUnit($business_id, 'كرتون', 'كرتون', $created_by, false);

        $products = [
            // A) Raw ingredients
            [
                'name' => 'سكر',
                'sku' => 'PKG-SUGAR',
                'unit_id' => $unit_kg,
                'usage' => 'raw_ingredient',
                'not_for_selling' => 1,
                'opening_stock' => 200,
                'purchase_price' => 20,
                'sell_price' => 20,
            ],
            [
                'name' => 'قشطة / كريمة',
                'sku' => 'PKG-CREAM',
                'unit_id' => $unit_liter,
                'usage' => 'raw_ingredient',
                'not_for_selling' => 1,
                'opening_stock' => 50,
                'purchase_price' => 40,
                'sell_price' => 40,
            ],
            [
                'name' => 'زبدة',
                'sku' => 'PKG-BUTTER',
                'unit_id' => $unit_kg,
                'usage' => 'raw_ingredient',
                'not_for_selling' => 1,
                'opening_stock' => 30,
                'purchase_price' => 80,
                'sell_price' => 80,
            ],
            // B) Bulk finished
            [
                'name' => 'صوص كراميل — جملة',
                'sku' => 'CRM-BULK',
                'unit_id' => $unit_kg,
                'usage' => 'bulk_finished',
                'not_for_selling' => 0,
                'opening_stock' => 0,
                'purchase_price' => 50,
                'sell_price' => 60,
            ],
            // C) Packaging materials
            [
                'name' => 'زجاجة 500 مل فارغة',
                'sku' => 'PKG-BOTTLE-500',
                'unit_id' => $unit_pcs,
                'usage' => 'packaging_material',
                'not_for_selling' => 1,
                'opening_stock' => 500,
                'purchase_price' => 2,
                'sell_price' => 2,
            ],
            [
                'name' => 'غطاء زجاجة',
                'sku' => 'PKG-CAP',
                'unit_id' => $unit_pcs,
                'usage' => 'packaging_material',
                'not_for_selling' => 1,
                'opening_stock' => 500,
                'purchase_price' => 0.5,
                'sell_price' => 0.5,
            ],
            [
                'name' => 'ملصق / ستيكر كراميل',
                'sku' => 'PKG-LABEL-CRM',
                'unit_id' => $unit_pcs,
                'usage' => 'packaging_material',
                'not_for_selling' => 1,
                'opening_stock' => 500,
                'purchase_price' => 0.3,
                'sell_price' => 0.3,
            ],
            [
                'name' => 'كرتون فارغ (12×500مل)',
                'sku' => 'PKG-CARTON-EMPTY',
                'unit_id' => $unit_pcs,
                'usage' => 'packaging_material',
                'not_for_selling' => 1,
                'opening_stock' => 50,
                'purchase_price' => 5,
                'sell_price' => 5,
            ],
            // D) Packaged finished (POS)
            [
                'name' => 'صوص كراميل — كرتون 12×500مل',
                'sku' => 'CRM-CTN-12x500',
                'unit_id' => $unit_carton,
                'usage' => 'packaged_finished',
                'not_for_selling' => 0,
                'opening_stock' => 0,
                'purchase_price' => 150,
                'sell_price' => 180,
            ],
        ];

        $created = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($products as $item) {
                $existing = Product::where('business_id', $business_id)
                    ->where(function ($q) use ($item) {
                        $q->where('sku', $item['sku'])
                            ->orWhere('name', $item['name']);
                    })
                    ->first();

                if ($existing) {
                    $existing->product_usage_type = $item['usage'];
                    $existing->not_for_selling = $item['not_for_selling'];
                    $existing->save();
                    $existing->product_locations()->syncWithoutDetaching([$location_id]);
                    $skipped++;
                    $this->command?->line('Updated existing: ' . $item['name']);
                    continue;
                }

                $product = Product::create([
                    'name' => $item['name'],
                    'business_id' => $business_id,
                    'type' => 'single',
                    'product_usage_type' => $item['usage'],
                    'unit_id' => $item['unit_id'],
                    'tax_type' => 'exclusive',
                    'enable_stock' => 1,
                    'sku' => $item['sku'],
                    'barcode_type' => 'C128',
                    'created_by' => $created_by,
                    'not_for_selling' => $item['not_for_selling'],
                    'is_inactive' => 0,
                ]);

                $product->product_locations()->sync([$location_id]);

                $product_variation = $product->product_variations()->create([
                    'name' => 'DUMMY',
                    'is_dummy' => 1,
                ]);

                $variation = Variation::create([
                    'name' => 'DUMMY',
                    'product_id' => $product->id,
                    'product_variation_id' => $product_variation->id,
                    'sub_sku' => $item['sku'],
                    'default_purchase_price' => $item['purchase_price'],
                    'dpp_inc_tax' => $item['purchase_price'],
                    'profit_percent' => 0,
                    'default_sell_price' => $item['sell_price'],
                    'sell_price_inc_tax' => $item['sell_price'],
                ]);

                if (! empty($item['opening_stock']) && $item['opening_stock'] > 0) {
                    VariationLocationDetails::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'product_variation_id' => $product_variation->id,
                            'variation_id' => $variation->id,
                            'location_id' => $location_id,
                        ],
                        [
                            'qty_available' => $item['opening_stock'],
                        ]
                    );
                }

                $created++;
                $this->command?->info('Created: ' . $item['name']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command?->error($e->getMessage());
            throw $e;
        }

        $this->command?->info("Packaging demo products done for business #{$business_id} ({$business->name}).");
        $this->command?->info("Created: {$created}, Updated existing: {$skipped}, Location: {$location_id}");
        $this->command?->info('Next: Manufacturing → Recipe / Packaging Profiles (steps 2–4 in Feature_In_Arabic.md)');
    }

    protected function ensureUnit($business_id, $actual_name, $short_name, $created_by, $allow_decimal)
    {
        $unit = Unit::where('business_id', $business_id)
            ->where(function ($q) use ($actual_name, $short_name) {
                $q->where('actual_name', $actual_name)
                    ->orWhere('short_name', $short_name);
            })
            ->first();

        if ($unit) {
            return $unit->id;
        }

        // Fallback to any existing similar unit for this business
        $fallback = Unit::where('business_id', $business_id)->orderBy('id')->first();
        if ($fallback && in_array(strtolower($short_name), ['قطعة', 'pcs', 'pc(s)'])) {
            return $fallback->id;
        }

        $unit = Unit::create([
            'business_id' => $business_id,
            'actual_name' => $actual_name,
            'short_name' => $short_name,
            'allow_decimal' => $allow_decimal ? 1 : 0,
            'created_by' => $created_by,
        ]);

        return $unit->id;
    }
}
