<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitVariationIdToMfgPackagingProfilesTable extends Migration
{
    public function up()
    {
        Schema::table('mfg_packaging_profiles', function (Blueprint $table) {
            $table->integer('unit_variation_id')->unsigned()->nullable()->after('output_variation_id');
        });
    }

    public function down()
    {
        Schema::table('mfg_packaging_profiles', function (Blueprint $table) {
            $table->dropColumn('unit_variation_id');
        });
    }
}
