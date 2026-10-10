<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsesCartonToMfgPackagingProfilesTable extends Migration
{
    public function up()
    {
        Schema::table('mfg_packaging_profiles', function (Blueprint $table) {
            $table->boolean('uses_carton')->default(1)->after('container_volume');
        });
    }

    public function down()
    {
        Schema::table('mfg_packaging_profiles', function (Blueprint $table) {
            $table->dropColumn('uses_carton');
        });
    }
}
