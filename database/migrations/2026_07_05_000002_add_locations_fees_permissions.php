<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'locations_fees.access']);
        Permission::firstOrCreate(['name' => 'locations_fees.create']);
        Permission::firstOrCreate(['name' => 'locations_fees.update']);
        Permission::firstOrCreate(['name' => 'locations_fees.delete']);
    }

    public function down(): void
    {
        //
    }
};
