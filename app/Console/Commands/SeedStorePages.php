<?php

namespace App\Console\Commands;

use Database\Seeders\StorePagesSeeder;
use Illuminate\Console\Command;

class SeedStorePages extends Command
{
    protected $signature = 'store-pages:seed
                            {--business_id=* : Business ID(s) to seed; omit to seed all businesses}
                            {--force : Replace existing store pages}';

    protected $description = 'Seed default store content pages (privacy, terms, warranty, etc.)';

    public function handle(): int
    {
        $seeder = new StorePagesSeeder;
        $seeder->setCommand($this);

        $businessIds = array_map('intval', (array) $this->option('business_id'));
        if ($businessIds !== []) {
            $seeder->businessIds = $businessIds;
        }

        $seeder->force = (bool) $this->option('force');
        $seeder->run();

        return self::SUCCESS;
    }
}
