<?php

namespace App\Console\Commands;

use App\Services\IpGeolocationService;
use App\WebsiteVisitLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixWebsiteVisitLogs extends Command
{
    protected $signature = 'website-logs:fix-old
                            {--hours=2.5 : Hours to subtract from old timestamps (Kolkata→Cairo ≈ 2.5)}
                            {--before= : Only rows created before this datetime (Y-m-d H:i:s). Default: now}
                            {--timestamps : Shift DB timestamps (usually unnecessary; admin already shows Egypt time)}
                            {--locations : Re-resolve locations from IP (skips browser/cloudflare)}
                            {--limit=500 : Max rows for location refresh}
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Fix old website visit logs (optional timestamp shift and/or IP locations)';

    public function handle(IpGeolocationService $geo): int
    {
        $doTimestamps = (bool) $this->option('timestamps');
        $doLocations = (bool) $this->option('locations');
        $dryRun = (bool) $this->option('dry-run');

        if (! $doTimestamps && ! $doLocations) {
            // Default: locations only — timestamps are converted to Egypt on display.
            $doLocations = true;
        }

        if ($dryRun) {
            $this->warn('Dry run — no changes will be saved.');
        }

        $fixedTimestamps = 0;
        $fixedLocations = 0;

        if ($doTimestamps) {
            $fixedTimestamps = $this->fixTimestamps($dryRun);
        }

        if ($doLocations) {
            $limit = max(1, (int) $this->option('limit'));
            if ($dryRun) {
                $count = WebsiteVisitLog::query()
                    ->whereNotNull('ip_address')
                    ->where(function ($q) {
                        $q->whereNull('location_source')
                            ->orWhere('location_source', 'ip');
                    })
                    ->count();
                $this->info("Would refresh locations for up to {$limit} of {$count} IP-sourced row(s).");
            } else {
                $this->info("Refreshing locations (limit {$limit})...");
                $fixedLocations = $geo->backfillMissing($limit, true);
                $this->info("Location refresh processed {$fixedLocations} row(s).");
            }
        }

        $this->info("Done. Timestamps fixed: {$fixedTimestamps}. Locations processed: {$fixedLocations}.");

        return self::SUCCESS;
    }

    private function fixTimestamps(bool $dryRun): int
    {
        $hours = (float) $this->option('hours');
        if ($hours == 0.0) {
            $this->warn('hours=0 — skipping timestamp fix.');

            return 0;
        }

        $minutes = (int) round(abs($hours) * 60);
        if ($minutes === 0) {
            $this->warn('hours too small — skipping timestamp fix.');

            return 0;
        }

        $before = $this->option('before') ?: now()->format('Y-m-d H:i:s');

        $query = WebsiteVisitLog::query()->where('created_at', '<', $before);
        $count = (clone $query)->count();

        $this->info("Timestamp fix: {$count} row(s) with created_at < {$before}, shift {$hours} hour(s) ({$minutes} min).");

        if ($count === 0 || $dryRun) {
            return $dryRun ? $count : 0;
        }

        // Shift wall-clock timestamps in SQL to avoid Carbon timezone conversion.
        $sign = $hours >= 0 ? '-' : '+';
        $updated = DB::table('website_visit_logs')
            ->where('created_at', '<', $before)
            ->update([
                'created_at' => DB::raw("DATE_ADD(created_at, INTERVAL {$sign}{$minutes} MINUTE)"),
                'updated_at' => DB::raw("DATE_ADD(updated_at, INTERVAL {$sign}{$minutes} MINUTE)"),
                'last_activity_at' => DB::raw("CASE WHEN last_activity_at IS NULL THEN NULL ELSE DATE_ADD(last_activity_at, INTERVAL {$sign}{$minutes} MINUTE) END"),
            ]);

        $this->info("Updated timestamps on {$updated} row(s).");

        return (int) $updated;
    }
}
