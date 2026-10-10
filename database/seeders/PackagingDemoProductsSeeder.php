<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Wrapper so you can run:
 *   php artisan db:seed --class=PackagingDemoProductsSeeder
 *
 * Or set in .env:
 *   MFG_PACKAGING_SEED_BUSINESS_ID=YOUR_BUSINESS_ID
 */
class PackagingDemoProductsSeeder extends Seeder
{
    public function run()
    {
        $this->call(\Modules\Manufacturing\Database\Seeders\PackagingDemoProductsSeeder::class);
    }
}
