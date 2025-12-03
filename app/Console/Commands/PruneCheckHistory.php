<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Check;
use Illuminate\Console\Command;

class PruneCheckHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checks:prune {--days= : Number of days to retain (overrides config)} {--dry-run : Display what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete check history older than the configured retention period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get retention days from option or config
        $retention_days = $this->option('days')
            ? (int) $this->option('days')
            : config('monitoring.check_retention_days');

        // If retention is 0, keep all history
        if ($retention_days <= 0) {
            $this->info('Check retention is set to 0. No checks will be deleted.');

            return self::SUCCESS;
        }

        // Calculate cutoff date
        $cutoff_date = now()->subDays($retention_days);

        $this->info("Deleting checks older than {$retention_days} days (before {$cutoff_date->toDateTimeString()})...");

        // Count checks to be deleted
        $count = Check::where('checked_at', '<', $cutoff_date)->count();

        if ($count === 0) {
            $this->info('No checks found to delete.');

            return self::SUCCESS;
        }

        // Handle dry-run mode
        if ($this->option('dry-run')) {
            $this->warn("DRY RUN: Would delete {$count} check record(s)");

            // Show sample of what would be deleted
            $sample = Check::where('checked_at', '<', $cutoff_date)
                ->orderBy('checked_at', 'asc')
                ->limit(5)
                ->get(['id', 'monitor_id', 'checked_at', 'status']);

            if ($sample->isNotEmpty()) {
                $this->table(
                    ['ID', 'Monitor ID', 'Checked At', 'Status'],
                    $sample->map(fn ($check) => [
                        $check->id,
                        $check->monitor_id,
                        $check->checked_at,
                        $check->status,
                    ])
                );

                if ($count > 5) {
                    $this->line('... and '.($count - 5).' more');
                }
            }

            return self::SUCCESS;
        }

        // Delete old checks
        $deleted = Check::where('checked_at', '<', $cutoff_date)->delete();

        $this->info("Successfully deleted {$deleted} check record(s).");

        return self::SUCCESS;
    }
}
