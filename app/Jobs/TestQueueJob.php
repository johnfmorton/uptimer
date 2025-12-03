<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TestQueueJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $message,
        public ?string $test_id = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $start_time = now();
        
        // Update cache status to processing
        if ($this->test_id) {
            Cache::put("queue_test_{$this->test_id}", [
                'status' => 'processing',
                'message' => 'Test job is being processed...',
                'started_at' => $start_time->toDateTimeString(),
            ], now()->addMinutes(5));
        }
        
        // Log the test message to verify queue is working
        Log::info('🚀 Queue Test Job Started', [
            'job_id' => $this->job?->getJobId(),
            'test_id' => $this->test_id,
            'message' => $this->message,
            'queue' => $this->queue ?? 'default',
            'started_at' => $start_time->toDateTimeString(),
        ]);

        // Simulate some work
        sleep(2);

        $end_time = now();
        $duration = $end_time->diffInSeconds($start_time);

        // Update cache status to completed
        if ($this->test_id) {
            Cache::put("queue_test_{$this->test_id}", [
                'status' => 'completed',
                'message' => "✅ Queue test completed successfully in {$duration} seconds! Queue system is working properly.",
                'started_at' => $start_time->toDateTimeString(),
                'completed_at' => $end_time->toDateTimeString(),
                'duration_seconds' => $duration,
            ], now()->addMinutes(5));
        }

        Log::info('✅ Queue Test Job Successfully Completed - Queue System is Working!', [
            'job_id' => $this->job?->getJobId(),
            'test_id' => $this->test_id,
            'message' => $this->message,
            'queue' => $this->queue ?? 'default',
            'started_at' => $start_time->toDateTimeString(),
            'completed_at' => $end_time->toDateTimeString(),
            'duration_seconds' => $duration,
            'status' => 'success',
        ]);
    }
}
