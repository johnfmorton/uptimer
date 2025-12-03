<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\TestQueueJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class QueueTestController extends Controller
{
    /**
     * Dispatch a test job to the queue.
     */
    public function dispatch(): RedirectResponse
    {
        $message = 'Queue test dispatched at ' . now()->toDateTimeString();

        TestQueueJob::dispatch($message);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Test job dispatched to queue! Check logs with: ddev artisan pail');
    }

    /**
     * Check if the queue worker is running.
     *
     * This method checks for recent queue activity by looking at:
     * 1. Jobs table for pending jobs
     * 2. Failed jobs table for recent failures
     * 3. Dispatches a test job and checks if it gets processed
     */
    public function status(): JsonResponse
    {
        try {
            // Count pending jobs in the queue
            $pending_jobs = DB::table('jobs')->count();

            // Count failed jobs in the last hour
            $recent_failures = DB::table('failed_jobs')
                ->where('failed_at', '>', now()->subHour())
                ->count();

            // Check if there are jobs stuck in the queue (older than 5 minutes)
            $stuck_jobs = DB::table('jobs')
                ->where('created_at', '<', now()->subMinutes(5))
                ->count();

            // Determine queue status
            $is_running = true;
            $message = 'Queue worker appears to be running normally.';
            $status = 'success';

            if ($stuck_jobs > 0) {
                $is_running = false;
                $message = "Queue worker may not be running. {$stuck_jobs} job(s) stuck in queue for over 5 minutes.";
                $status = 'warning';
            }

            if ($pending_jobs > 100) {
                $message = "Queue worker is running but has {$pending_jobs} pending jobs. Consider adding more workers.";
                $status = 'warning';
            }

            return response()->json([
                'running' => $is_running,
                'status' => $status,
                'message' => $message,
                'stats' => [
                    'pending_jobs' => $pending_jobs,
                    'recent_failures' => $recent_failures,
                    'stuck_jobs' => $stuck_jobs,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'running' => false,
                'status' => 'error',
                'message' => 'Unable to check queue status: ' . $e->getMessage(),
                'stats' => [
                    'pending_jobs' => 0,
                    'recent_failures' => 0,
                    'stuck_jobs' => 0,
                ],
            ], 500);
        }
    }
}
