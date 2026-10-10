<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMfgPackagingProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('mfg_packaging_profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->string('name');
            $table->integer('bulk_variation_id')->unsigned();
            $table->integer('output_variation_id')->unsigned();
            $table->enum('container_type', ['bottle', 'bag']);
            $table->decimal('container_volume', 22, 4)->nullable();
            $table->integer('units_per_carton');
            $table->decimal('bulk_qty_per_container', 22, 4);
            $table->decimal('waste_percent', 8, 4)->nullable()->default(0);
            $table->boolean('is_active')->default(1);
            $table->text('instructions')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mfg_packaging_profiles');
    }
}
