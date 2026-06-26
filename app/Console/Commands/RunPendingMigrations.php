<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RunPendingMigrations extends Command
{
    protected $signature = 'pos:runPendingMigrations
                            {--check : Only report which migrations have or have not run}
                            {--force : Run migrations without confirmation prompt}';

    protected $description = 'Ensure migrations listed in migrations_need_to_run have been executed';

    public function handle(): int
    {
        $listPath = base_path('migrations_need_to_run');

        if (! File::exists($listPath)) {
            $this->error('File not found: migrations_need_to_run');

            return self::FAILURE;
        }

        $lines = collect(file($listPath, FILE_IGNORE_NEW_LINES))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '' && ! str_starts_with($line, '#'));

        if ($lines->isEmpty()) {
            $this->info('No migrations listed in migrations_need_to_run.');

            return self::SUCCESS;
        }

        $executed = DB::table('migrations')->pluck('migration')->all();
        $pending = [];
        $alreadyRun = [];
        $missing = [];

        foreach ($lines as $line) {
            $entry = str_ends_with($line, '.php') ? $line : $line.'.php';
            $migration = pathinfo($entry, PATHINFO_FILENAME);
            $relativePath = str_contains($entry, '/')
                ? $entry
                : 'database/migrations/'.$entry;
            $absolutePath = base_path($relativePath);

            if (! File::exists($absolutePath)) {
                $missing[] = $entry;
                continue;
            }

            if (in_array($migration, $executed, true)) {
                $alreadyRun[] = $migration;
            } else {
                $pending[] = [
                    'migration' => $migration,
                    'path' => $relativePath,
                ];
            }
        }

        if ($missing !== []) {
            $this->warn('Migration files not found:');
            foreach ($missing as $filename) {
                $this->line("  - {$filename}");
            }
        }

        if ($alreadyRun !== []) {
            $this->info('Already executed:');
            foreach ($alreadyRun as $migration) {
                $this->line("  ✓ {$migration}");
            }
        }

        if ($pending === []) {
            $this->info('All listed migrations are already executed.');

            return $missing === [] ? self::SUCCESS : self::FAILURE;
        }

        $this->warn('Pending migrations:');
        foreach ($pending as $item) {
            $this->line("  - {$item['migration']}");
        }

        if ($this->option('check')) {
            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Run '.count($pending).' pending migration(s)?', true)) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        foreach ($pending as $item) {
            $this->info("Running: {$item['migration']}");

            $exitCode = $this->call('migrate', [
                '--path' => $item['path'],
                '--force' => true,
            ]);

            if ($exitCode !== self::SUCCESS) {
                $this->error("Failed to run: {$item['migration']}");

                return self::FAILURE;
            }
        }

        $this->info('All pending migrations executed successfully.');

        return self::SUCCESS;
    }
}
