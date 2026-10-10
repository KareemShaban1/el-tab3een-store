<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMfgPackagingMaterialsTable extends Migration
{
    public function up()
    {
        Schema::create('mfg_packaging_materials', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('packaging_profile_id')->unsigned();
            $table->integer('variation_id')->unsigned();
            $table->decimal('quantity_per_container', 22, 4)->nullable();
            $table->decimal('quantity_per_carton', 22, 4)->nullable();
            $table->enum('material_role', ['container', 'closure', 'label', 'outer_carton', 'other'])->nullable();
            $table->integer('sub_unit_id')->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('packaging_profile_id')
                ->references('id')
                ->on('mfg_packaging_profiles')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mfg_packaging_materials');
    }
}
