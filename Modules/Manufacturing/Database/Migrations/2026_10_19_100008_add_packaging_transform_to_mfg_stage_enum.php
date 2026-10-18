<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddPackagingTransformToMfgStageEnum extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN mfg_stage ENUM('cooking', 'packaging', 'packaging_transform') NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN mfg_stage ENUM('cooking', 'packaging') NULL");
    }
}
