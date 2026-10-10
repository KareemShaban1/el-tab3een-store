<?php

namespace Modules\Manufacturing\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class ManufacturingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // $this->call("OthersTableSeeder");
        // Demo products for packaging workflow (docs/Feature_In_Arabic.md step 1):
        // $this->call(PackagingDemoProductsSeeder::class);
    }
}
