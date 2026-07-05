<?php

namespace App\Console\Commands;

use Database\Seeders\LocationsFeesSeeder;
use Illuminate\Console\Command;

class SeedLocationsFees extends Command
{
    protected $signature = 'locations-fees:seed
                            {--business_id=* : Business ID(s) to seed; omit to seed all businesses}
                            {--force : Replace existing locations for the selected business(es)}
                            {--city-cost=50 : Default delivery cost for cities}
                            {--area-cost=50 : Default delivery cost for areas}';

    protected $description = 'Seed Egypt governorates, cities, and areas with default delivery costs';

    public function handle(): int
    {
        $seeder = new LocationsFeesSeeder;
        $seeder->setCommand($this);

        $businessIds = array_map('intval', (array) $this->option('business_id'));
        if ($businessIds !== []) {
            $seeder->businessIds = $businessIds;
        }

        $seeder->force = (bool) $this->option('force');
        $seeder->cityDeliveryCost = (float) $this->option('city-cost');
        $seeder->areaDeliveryCost = (float) $this->option('area-cost');

        $seeder->run();

        return self::SUCCESS;
    }
}
