<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

class AddPackagingPermissions extends Migration
{
    public function up()
    {
        Permission::firstOrCreate(['name' => 'manufacturing.access_packaging']);
        Permission::firstOrCreate(['name' => 'manufacturing.manage_packaging_profiles']);
    }

    public function down()
    {
    }
}
