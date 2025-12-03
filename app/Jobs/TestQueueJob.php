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
        // Log the test message to verify queue is working
        Log::info('Test Queue Job Executed', [
            'message' => $this->message,
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Simulate some work
        sleep(2);

        Log::info('Test Queue Job Completed', [
            'message' => $this->message,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
