<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMfgProductionPackagingUsagesTable extends Migration
{
    public function up()
    {
        Schema::create('mfg_production_packaging_usages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('production_purchase_id')->unsigned();
            $table->integer('variation_id')->unsigned();
            $table->decimal('quantity', 22, 4);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->integer('sell_line_id')->unsigned()->nullable();
            $table->timestamps();

            $table->index('production_purchase_id');
            $table->index('variation_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mfg_production_packaging_usages');
    }
}
