<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\TestQueueJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QueueDiagnosticsService
{
    /**
     * Get comprehensive queue diagnostics.
     *
     * @return array{
     *     pending_jobs: int,
     *     failed_jobs_last_hour: int,
     *     stuck_jobs: int,
     *     queue_worker_running: bool,
     *     scheduler_running: bool,
     *     has_issues: bool
     * }
     */
    public function getQueueDiagnostics(): array
    {
        $pending_jobs = $this->getPendingJobsCount();
        $failed_jobs_last_hour = $this->getFailedJobsCount();
        $stuck_jobs = $this->getStuckJobsCount();
        $queue_worker_running = $this->isQueueWorkerRunning();
        $scheduler_running = $this->isSchedulerRunning();

        $has_issues = ! $queue_worker_running || ! $scheduler_running || $stuck_jobs > 0;

        return [
            'pending_jobs' => $pending_jobs,
            'failed_jobs_last_hour' => $failed_jobs_last_hour,
            'stuck_jobs' => $stuck_jobs,
            'queue_worker_running' => $queue_worker_running,
            'scheduler_running' => $scheduler_running,
            'has_issues' => $has_issues,
        ];
    }

    /**
     * Get count of pending jobs in the queue.
     */
    public function getPendingJobsCount(): int
    {
        return DB::table('jobs')
            ->whereNull('reserved_at')
            ->count();
    }

    /**
     * Get count of failed jobs in the last hour.
     */
    public function getFailedJobsCount(): int
    {
        $one_hour_ago = now()->subMinutes(60);

        return DB::table('failed_jobs')
            ->where('failed_at', '>=', $one_hour_ago)
            ->count();
    }

    /**
     * Get count of stuck jobs (pending > 5 minutes).
     */
    public function getStuckJobsCount(): int
    {
        $five_minutes_ago = now()->subMinutes(5)->timestamp;

        return DB::table('jobs')
            ->whereNull('reserved_at')
            ->where('created_at', '<=', $five_minutes_ago)
            ->count();
    }

    /**
     * Check if queue worker appears to be running.
     */
    public function isQueueWorkerRunning(): bool
    {
        // Heuristic: If there are stuck jobs, the worker is likely not running
        return $this->getStuckJobsCount() === 0;
    }

    /**
     * Check if scheduler appears to be running.
     */
    public function isSchedulerRunning(): bool
    {
        // Check if the scheduler heartbeat cache key exists and is recent
        $heartbeat = Cache::get('scheduler:heartbeat');

        if ($heartbeat === null) {
            return false;
        }

        // Heartbeat should be updated every minute, consider stale if > 90 seconds old
        $heartbeat_age = now()->timestamp - $heartbeat;

        return $heartbeat_age < 90;
    }

    /**
     * Dispatch a test job to verify queue functionality.
     */
    public function dispatchTestJob(): void
    {
        TestQueueJob::dispatch('Queue test dispatched at '.now()->toDateTimeString());
    }
}
