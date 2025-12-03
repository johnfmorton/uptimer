<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendTestNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  User  $user  The user to send the test notification to
     * @param  string  $channel  The notification channel ('email' or 'pushover')
     */
    public function __construct(
        public User $user,
        public string $channel
    ) {
    }

    /**
     * Execute the job.
     *
     * This job sends a test notification through the specified channel
     * by delegating to NotificationService. Success and failures are logged
     * with user context for audit trail.
     *
     * @param  NotificationService  $notificationService
     * @return void
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            Log::info("Starting test {$this->channel} notification", [
                'user_id' => $this->user->id,
                'channel' => $this->channel,
            ]);

            if ($this->channel === 'email') {
                $notificationService->sendTestEmail($this->user);
                Log::info('Test email sent successfully', [
                    'user_id' => $this->user->id,
                ]);
            } elseif ($this->channel === 'pushover') {
                $notificationService->sendTestPushover($this->user);
                Log::info('Test Pushover sent successfully', [
                    'user_id' => $this->user->id,
                ]);
            } else {
                throw new \InvalidArgumentException("Invalid notification channel: {$this->channel}");
            }

        } catch (\Exception $e) {
            Log::error("Test {$this->channel} notification failed", [
                'user_id' => $this->user->id,
                'channel' => $this->channel,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw for job retry logic
            throw $e;
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
        Log::error("Test {$this->channel} notification job failed permanently", [
            'user_id' => $this->user->id,
            'channel' => $this->channel,
            'error' => $exception->getMessage(),
        ]);
    }
}
