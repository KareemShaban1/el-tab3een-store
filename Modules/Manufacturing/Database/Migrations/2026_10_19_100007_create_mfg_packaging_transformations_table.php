<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMfgPackagingTransformationsTable extends Migration
{
    public function up()
    {
        Schema::create('mfg_packaging_transformations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->integer('location_id')->unsigned();
            $table->integer('packaging_profile_id')->unsigned();
            $table->integer('carton_variation_id')->unsigned();
            $table->integer('unit_variation_id')->unsigned();
            $table->decimal('carton_qty', 22, 4);
            $table->decimal('unit_qty', 22, 4);
            $table->integer('units_per_carton')->unsigned();
            $table->string('ref_no')->nullable();
            $table->dateTime('transformed_at');
            $table->integer('created_by')->unsigned();
            $table->integer('production_purchase_id')->unsigned()->nullable();
            $table->integer('production_sell_id')->unsigned()->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'transformed_at']);
            $table->index('packaging_profile_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mfg_packaging_transformations');
    }
}
