<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\PerformMonitorCheck;
use App\Models\Monitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduleMonitorChecks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitors:schedule-checks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule check jobs for monitors that are due for checking';

    /**
     * Execute the console command.
     *
     * This command finds all monitors that are due for checking based on their
     * check_interval_minutes and last_checked_at timestamp, then dispatches
     * a PerformMonitorCheck job for each one.
     *
     * @return int Command exit code
     */
    public function handle(): int
    {
        $now = now();

        Log::info('Starting monitor check scheduling', [
            'timestamp' => $now->toDateTimeString(),
        ]);

        // Find monitors that are due for checking
        // A monitor is due if:
        // 1. It has never been checked (last_checked_at is null), OR
        // 2. The time since last check >= check_interval_minutes
        //
        // We fetch all monitors and filter in PHP for database compatibility
        // (SQLite doesn't support TIMESTAMPDIFF)
        $all_monitors = Monitor::all();

        $due_monitors = $all_monitors->filter(function ($monitor) use ($now) {
            // Never been checked - always due
            if ($monitor->last_checked_at === null) {
                return true;
            }

            // Calculate minutes since last check
            $minutes_since_check = $monitor->last_checked_at->diffInMinutes($now);

            // Due if enough time has passed
            return $minutes_since_check >= $monitor->check_interval_minutes;
        });

        $count = $due_monitors->count();

        if ($count === 0) {
            $this->info('No monitors due for checking');
            Log::info('No monitors due for checking');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} monitor(s) due for checking");
        Log::info("Found monitors due for checking", ['count' => $count]);

        // Dispatch a check job for each due monitor
        foreach ($due_monitors as $monitor) {
            PerformMonitorCheck::dispatch($monitor);

            $this->line("Dispatched check for: {$monitor->name} (ID: {$monitor->id})");
            Log::info('Dispatched monitor check job', [
                'monitor_id' => $monitor->id,
                'monitor_name' => $monitor->name,
                'monitor_url' => $monitor->url,
            ]);
        }

        $this->info("Successfully dispatched {$count} check job(s)");
        Log::info('Monitor check scheduling completed', [
            'dispatched_count' => $count,
        ]);

        return Command::SUCCESS;
    }
}
