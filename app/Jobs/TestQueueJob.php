<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TestQueueJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $message
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $start_time = now();
        
        // Log the test message to verify queue is working
        Log::info('🚀 Queue Test Job Started', [
            'job_id' => $this->job?->getJobId(),
            'message' => $this->message,
            'queue' => $this->queue ?? 'default',
            'started_at' => $start_time->toDateTimeString(),
        ]);

        // Simulate some work
        sleep(2);

        $end_time = now();
        $duration = $end_time->diffInSeconds($start_time);

        Log::info('✅ Queue Test Job Successfully Completed - Queue System is Working!', [
            'job_id' => $this->job?->getJobId(),
            'message' => $this->message,
            'queue' => $this->queue ?? 'default',
            'started_at' => $start_time->toDateTimeString(),
            'completed_at' => $end_time->toDateTimeString(),
            'duration_seconds' => $duration,
            'status' => 'success',
        ]);
    }
}
