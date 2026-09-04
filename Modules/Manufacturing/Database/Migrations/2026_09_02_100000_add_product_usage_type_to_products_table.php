<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductUsageTypeToProductsTable extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('product_usage_type', [
                'raw_ingredient',
                'bulk_finished',
                'packaging_material',
                'packaged_finished',
            ])->nullable()->after('type');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('product_usage_type');
        });
    }
}
