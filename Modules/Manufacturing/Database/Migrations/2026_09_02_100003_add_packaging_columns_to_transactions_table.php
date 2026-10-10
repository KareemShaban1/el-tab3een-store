<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPackagingColumnsToTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('mfg_stage', ['cooking', 'packaging'])->nullable()->after('mfg_is_final');
            $table->integer('mfg_packaging_profile_id')->unsigned()->nullable()->after('mfg_stage');
            $table->integer('mfg_containers_count')->nullable()->after('mfg_packaging_profile_id');
            $table->integer('mfg_cartons_count')->nullable()->after('mfg_containers_count');
            $table->enum('mfg_container_type', ['bottle', 'bag'])->nullable()->after('mfg_cartons_count');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'mfg_stage',
                'mfg_packaging_profile_id',
                'mfg_containers_count',
                'mfg_cartons_count',
                'mfg_container_type',
            ]);
        });
    }
}
