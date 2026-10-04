<?php

namespace App\Console\Commands;

use App\Services\IpGeolocationService;
use Illuminate\Console\Command;

class RefreshWebsiteVisitLocations extends Command
{
    protected $signature = 'website-logs:refresh-locations
                            {--limit=100 : Max rows to process}
                            {--force : Re-resolve even if location already exists}';

    protected $description = 'Refresh / backfill location data for website visit logs from IP addresses';

    public function handle(IpGeolocationService $geo): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $force = (bool) $this->option('force');

        $this->info("Refreshing up to {$limit} visit log location(s)...");
        $count = $geo->backfillMissing($limit, $force);
        $this->info("Processed {$count} row(s).");

        return self::SUCCESS;
    }
}
