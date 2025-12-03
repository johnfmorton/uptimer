<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\PerformMonitorCheck;
use App\Models\Monitor;
use App\Models\User;
use App\Services\CheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PerformMonitorCheckJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the job can be dispatched to the queue.
     *
     * @return void
     */
    public function test_job_can_be_dispatched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $monitor = Monitor::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
        ]);

        PerformMonitorCheck::dispatch($monitor);

        Queue::assertPushed(PerformMonitorCheck::class, function ($job) use ($monitor) {
            return $job->monitor->id === $monitor->id;
        });
    }

    /**
     * Test that the job calls CheckService to perform the check.
     *
     * @return void
     */
    public function test_job_calls_check_service(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'status' => 'pending',
        ]);

        $checkService = $this->createMock(CheckService::class);
        $checkService->expects($this->once())
            ->method('performCheck')
            ->with($monitor);

        $job = new PerformMonitorCheck($monitor);
        $job->handle($checkService);
    }

    /**
     * Test that job logs errors but doesn't throw exceptions.
     *
     * @return void
     */
    public function test_job_handles_exceptions_gracefully(): void
    {
        Log::spy();

        $user = User::factory()->create();
        $monitor = Monitor::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
        ]);

        $checkService = $this->createMock(CheckService::class);
        $checkService->expects($this->once())
            ->method('performCheck')
            ->willThrowException(new \Exception('Test exception'));

        $job = new PerformMonitorCheck($monitor);
        
        // Should not throw exception
        $job->handle($checkService);

        // Should log the error
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Monitor check job failed', \Mockery::type('array'));
    }

    /**
     * Test that job logs successful check completion.
     *
     * @return void
     */
    public function test_job_logs_successful_completion(): void
    {
        Log::spy();

        $user = User::factory()->create();
        $monitor = Monitor::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
        ]);

        $check = new \App\Models\Check([
            'id' => 1,
            'monitor_id' => $monitor->id,
            'status' => 'success',
            'status_code' => 200,
            'response_time_ms' => 150,
        ]);

        $checkService = $this->createMock(CheckService::class);
        $checkService->expects($this->once())
            ->method('performCheck')
            ->willReturn($check);

        $job = new PerformMonitorCheck($monitor);
        $job->handle($checkService);

        // Should log start and completion
        Log::shouldHaveReceived('info')
            ->with('Starting monitor check', \Mockery::type('array'));
        
        Log::shouldHaveReceived('info')
            ->with('Monitor check completed', \Mockery::type('array'));
    }
}

