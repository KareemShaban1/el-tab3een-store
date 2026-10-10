<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('discounts', function (Blueprint $table) {
            if (! Schema::hasColumn('discounts', 'sub_category_id')) {
                $table->integer('sub_category_id')->nullable()->after('category_id')->index();
            }
            if (! Schema::hasColumn('discounts', 'include_sub_categories')) {
                $table->boolean('include_sub_categories')->default(0)->after('sub_category_id');
            }
            if (! Schema::hasColumn('discounts', 'product_id')) {
                $table->integer('product_id')->nullable()->after('include_sub_categories')->index();
            }
            if (! Schema::hasColumn('discounts', 'apply_on_all_variations')) {
                $table->boolean('apply_on_all_variations')->default(0)->after('product_id');
            }
            if (! Schema::hasColumn('discounts', 'discount_kind')) {
                $table->string('discount_kind', 20)->default('standard')->after('name')->index();
            }
            if (! Schema::hasColumn('discounts', 'apply_in_pos')) {
                $table->boolean('apply_in_pos')->default(1)->after('is_active');
            }
            if (! Schema::hasColumn('discounts', 'apply_in_ecommerce')) {
                $table->boolean('apply_in_ecommerce')->default(1)->after('apply_in_pos');
            }
            if (! Schema::hasColumn('discounts', 'cg_mode')) {
                $table->string('cg_mode', 30)->default('any')->after('applicable_in_cg')->index();
            }
            if (! Schema::hasColumn('discounts', 'max_redemptions_total')) {
                $table->integer('max_redemptions_total')->nullable()->after('cg_mode');
            }
            if (! Schema::hasColumn('discounts', 'max_redemptions_per_customer')) {
                $table->integer('max_redemptions_per_customer')->nullable()->after('max_redemptions_total');
            }
            if (! Schema::hasColumn('discounts', 'max_qty_per_order')) {
                $table->decimal('max_qty_per_order', 22, 4)->nullable()->after('max_redemptions_per_customer');
            }
            if (! Schema::hasColumn('discounts', 'flash_quota_qty')) {
                $table->decimal('flash_quota_qty', 22, 4)->nullable()->after('max_qty_per_order');
            }
            if (! Schema::hasColumn('discounts', 'flash_sold_qty')) {
                $table->decimal('flash_sold_qty', 22, 4)->default(0)->after('flash_quota_qty');
            }
            if (! Schema::hasColumn('discounts', 'banner_title')) {
                $table->string('banner_title', 191)->nullable()->after('flash_sold_qty');
            }
            if (! Schema::hasColumn('discounts', 'banner_image')) {
                $table->string('banner_image', 255)->nullable()->after('banner_title');
            }
            if (! Schema::hasColumn('discounts', 'sort_order')) {
                $table->integer('sort_order')->nullable()->default(0)->after('banner_image');
            }
            if (! Schema::hasColumn('discounts', 'requires_promo_code')) {
                $table->boolean('requires_promo_code')->default(0)->after('sort_order')->index();
            }
            if (! Schema::hasColumn('discounts', 'promo_application')) {
                $table->string('promo_application', 20)->default('eligible_lines')->after('requires_promo_code');
            }
            if (! Schema::hasColumn('discounts', 'min_cart_subtotal')) {
                $table->decimal('min_cart_subtotal', 22, 4)->nullable()->after('promo_application');
            }
            if (! Schema::hasColumn('discounts', 'max_discount_amount')) {
                $table->decimal('max_discount_amount', 22, 4)->nullable()->after('min_cart_subtotal');
            }
            if (! Schema::hasColumn('discounts', 'first_order_only')) {
                $table->boolean('first_order_only')->default(0)->after('max_discount_amount');
            }
        });

        // Backfill cg_mode from legacy applicable_in_cg
        if (Schema::hasColumn('discounts', 'cg_mode') && Schema::hasColumn('discounts', 'applicable_in_cg')) {
            DB::table('discounts')->where('applicable_in_cg', 1)->update(['cg_mode' => 'with_group_only']);
            DB::table('discounts')->where(function ($q) {
                $q->whereNull('applicable_in_cg')->orWhere('applicable_in_cg', 0);
            })->update(['cg_mode' => 'any']);
        }

        if (! Schema::hasTable('discount_customer_groups')) {
            Schema::create('discount_customer_groups', function (Blueprint $table) {
                $table->integer('discount_id');
                $table->integer('customer_group_id');
                $table->primary(['discount_id', 'customer_group_id']);
                $table->index('customer_group_id');
            });
        }

        if (! Schema::hasTable('discount_redemptions')) {
            Schema::create('discount_redemptions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('discount_id')->index();
                $table->integer('promo_code_id')->nullable()->index();
                $table->string('redemption_source', 20)->default('automatic');
                $table->integer('transaction_id')->index();
                $table->integer('transaction_sell_line_id')->nullable();
                $table->integer('contact_id')->nullable()->index();
                $table->integer('variation_id')->nullable();
                $table->decimal('qty', 22, 4)->default(0);
                $table->decimal('discount_amount', 22, 4)->default(0);
                $table->integer('business_id')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('promo_codes')) {
            Schema::create('promo_codes', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('business_id')->index();
                $table->integer('discount_id')->index();
                $table->string('code', 50);
                $table->string('description', 255)->nullable();
                $table->boolean('is_active')->default(1);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->integer('max_uses_total')->nullable();
                $table->integer('max_uses_per_customer')->nullable();
                $table->integer('used_count')->default(0);
                $table->boolean('is_single_use')->default(0);
                $table->boolean('restore_on_return')->default(1);
                $table->integer('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id', 'code'], 'promo_codes_business_code_unique');
            });
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'promo_code_id')) {
                $table->integer('promo_code_id')->nullable()->after('discount_amount')->index();
            }
            if (! Schema::hasColumn('transactions', 'promo_code_text')) {
                $table->string('promo_code_text', 50)->nullable()->after('promo_code_id');
            }
            if (! Schema::hasColumn('transactions', 'promotional_discount_total')) {
                $table->decimal('promotional_discount_total', 22, 4)->default(0)->after('promo_code_text');
            }
            if (! Schema::hasColumn('transactions', 'promo_code_discount_total')) {
                $table->decimal('promo_code_discount_total', 22, 4)->default(0)->after('promotional_discount_total');
            }
        });

        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('transaction_sell_lines', 'discount_snapshot_name')) {
                $table->string('discount_snapshot_name', 191)->nullable()->after('discount_id');
            }
            if (! Schema::hasColumn('transaction_sell_lines', 'discount_snapshot_amount')) {
                $table->decimal('discount_snapshot_amount', 22, 4)->nullable()->after('discount_snapshot_name');
            }
            if (! Schema::hasColumn('transaction_sell_lines', 'discount_snapshot_type')) {
                $table->string('discount_snapshot_type', 20)->nullable()->after('discount_snapshot_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            foreach (['discount_snapshot_name', 'discount_snapshot_amount', 'discount_snapshot_type'] as $col) {
                if (Schema::hasColumn('transaction_sell_lines', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            foreach (['promo_code_id', 'promo_code_text', 'promotional_discount_total', 'promo_code_discount_total'] as $col) {
                if (Schema::hasColumn('transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('discount_redemptions');
        Schema::dropIfExists('discount_customer_groups');

        Schema::table('discounts', function (Blueprint $table) {
            $cols = [
                'sub_category_id', 'include_sub_categories', 'product_id', 'apply_on_all_variations',
                'discount_kind', 'apply_in_pos', 'apply_in_ecommerce', 'cg_mode',
                'max_redemptions_total', 'max_redemptions_per_customer', 'max_qty_per_order',
                'flash_quota_qty', 'flash_sold_qty', 'banner_title', 'banner_image', 'sort_order',
                'requires_promo_code', 'promo_application', 'min_cart_subtotal',
                'max_discount_amount', 'first_order_only',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('discounts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
