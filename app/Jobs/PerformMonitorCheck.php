<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Monitor;
use App\Services\CheckService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PerformMonitorCheck implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  Monitor  $monitor  The monitor to check
     */
    public function __construct(
        public Monitor $monitor
    ) {
    }

    /**
     * Execute the job.
     *
     * This job performs an HTTP check on the monitor by delegating to CheckService.
     * Errors are logged but do not prevent the next check from being scheduled.
     *
     * @param  CheckService  $checkService
     * @return void
     */
    public function handle(CheckService $checkService): void
    {
        try {
            Log::info('Starting monitor check', [
                'monitor_id' => $this->monitor->id,
                'monitor_name' => $this->monitor->name,
                'monitor_url' => $this->monitor->url,
            ]);

            // Perform the check via CheckService
            $check = $checkService->performCheck($this->monitor);

            Log::info('Monitor check completed', [
                'monitor_id' => $this->monitor->id,
                'check_id' => $check->id,
                'status' => $check->status,
                'status_code' => $check->status_code,
                'response_time_ms' => $check->response_time_ms,
            ]);

        } catch (\Exception $e) {
            // Log the error but don't throw - we want the next check to proceed normally
            Log::error('Monitor check job failed', [
                'monitor_id' => $this->monitor->id,
                'monitor_name' => $this->monitor->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't rethrow - let the job complete so the next check can be scheduled
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Monitor check job failed permanently', [
            'monitor_id' => $this->monitor->id,
            'monitor_name' => $this->monitor->name,
            'error' => $exception->getMessage(),
        ]);
    }
}

