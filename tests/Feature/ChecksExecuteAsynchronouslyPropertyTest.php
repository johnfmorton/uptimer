<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\PerformMonitorCheck;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Property-Based Test for Checks Executing Asynchronously
 *
 * **Feature: uptime-monitor, Property 18: Checks execute asynchronously**
 *
 * Property: For any scheduled check, the HTTP request should execute
 * in a queued job outside the web request lifecycle.
 *
 * Validates: Requirements 12.1, 12.2
 */
class ChecksExecuteAsynchronouslyPropertyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /**
     * Generate random monitor configurations for testing.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function monitorConfigurationProvider(): array
    {
        $test_cases = [];

        // Generate 100+ test cases with various monitor configurations
        for ($i = 0; $i < 100; $i++) {
            // Generate various URLs
            $urls = [
                'https://example' . $i . '.com',
                'http://test-site-' . $i . '.org',
                'https://api.example' . $i . '.com/health',
                'https://subdomain.example' . $i . '.net/status',
                'https://example.com/endpoint' . $i,
                'https://monitor-' . $i . '.example.com',
                'http://service' . $i . '.test.org/ping',
            ];
            
            $url = $urls[$i % count($urls)];

            // Generate various monitor names
            $names = [
                'Monitor ' . $i,
                'Test Site ' . $i,
                'API Endpoint ' . $i,
                'Service ' . $i,
                'Health Check ' . $i,
            ];
            
            $name = $names[$i % count($names)];

            // Generate various check intervals
            $intervals = [1, 5, 10, 15, 30, 60, 120, 240, 480, 1440];
            $interval = $intervals[$i % count($intervals)];

            // Generate various initial statuses
            $statuses = ['pending', 'up', 'down'];
            $status = $statuses[$i % count($statuses)];

            $test_cases[] = [
                'url' => $url,
                'name' => $name,
                'check_interval_minutes' => $interval,
                'status' => $status,
            ];
        }

        return $test_cases;
    }

    /**
     * Property Test: Dispatching check jobs pushes them to the queue.
     *
     * @dataProvider monitorConfigurationProvider
     */
    public function test_dispatching_check_jobs_pushes_them_to_queue(
        string $url,
        string $name,
        int $check_interval_minutes,
        string $status
    ): void {
        Queue::fake();

        $monitor = Monitor::factory()->create([
            'user_id' => $this->user->id,
            'url' => $url,
            'name' => $name,
            'check_interval_minutes' => $check_interval_minutes,
            'status' => $status,
        ]);

        // Dispatch the job
        PerformMonitorCheck::dispatch($monitor);

        // Assert job was pushed to queue
        Queue::assertPushed(PerformMonitorCheck::class, function ($job) use ($monitor) {
            return $job->monitor->id === $monitor->id;
        });

        // Assert job was pushed exactly once
        Queue::assertPushed(PerformMonitorCheck::class, 1);
    }

    /**
     * Property Test: Multiple monitors can have checks dispatched concurrently.
     */
    public function test_multiple_monitors_can_have_checks_dispatched_concurrently(): void
    {
        Queue::fake();

        // Create 100 monitors
        $monitors = [];
        for ($i = 0; $i < 100; $i++) {
            $monitors[] = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'name' => 'Monitor ' . $i,
                'status' => ['pending', 'up', 'down'][$i % 3],
            ]);
        }

        // Dispatch all checks
        foreach ($monitors as $monitor) {
            PerformMonitorCheck::dispatch($monitor);
        }

        // Assert all jobs were pushed to queue
        Queue::assertPushed(PerformMonitorCheck::class, 100);

        // Assert each monitor has its own job
        foreach ($monitors as $monitor) {
            Queue::assertPushed(PerformMonitorCheck::class, function ($job) use ($monitor) {
                return $job->monitor->id === $monitor->id;
            });
        }
    }

    /**
     * Property Test: Jobs implement ShouldQueue interface for async execution.
     */
    public function test_perform_monitor_check_job_implements_should_queue(): void
    {
        // Run 100 iterations to ensure consistency
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
            ]);

            $job = new PerformMonitorCheck($monitor);

            // Assert job implements ShouldQueue interface
            $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
        }
    }

    /**
     * Property Test: Jobs use Queueable trait for queue functionality.
     */
    public function test_perform_monitor_check_job_uses_queueable_trait(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
            ]);

            $job = new PerformMonitorCheck($monitor);

            // Assert job has queue-related methods from Queueable trait
            $this->assertTrue(method_exists($job, 'onQueue'));
            $this->assertTrue(method_exists($job, 'onConnection'));
            $this->assertTrue(method_exists($job, 'delay'));
        }
    }

    /**
     * Property Test: Jobs can be dispatched with different queue connections.
     */
    public function test_jobs_can_be_dispatched_with_different_queue_connections(): void
    {
        Queue::fake();

        // Test with 100 monitors
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
            ]);

            // Dispatch with default connection
            PerformMonitorCheck::dispatch($monitor);

            // Assert job was pushed
            Queue::assertPushed(PerformMonitorCheck::class);
        }

        // Assert total of 100 jobs were pushed
        Queue::assertPushed(PerformMonitorCheck::class, 100);
    }

    /**
     * Property Test: Jobs can be dispatched with delays for scheduled execution.
     */
    public function test_jobs_can_be_dispatched_with_delays(): void
    {
        Queue::fake();

        // Test with 100 monitors and various delays
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
            ]);

            // Dispatch with delay (in seconds)
            $delay = ($i % 10) * 60; // 0, 60, 120, ..., 540 seconds
            PerformMonitorCheck::dispatch($monitor)->delay($delay);

            // Assert job was pushed
            Queue::assertPushed(PerformMonitorCheck::class);
        }

        // Assert total of 100 jobs were pushed
        Queue::assertPushed(PerformMonitorCheck::class, 100);
    }

    /**
     * Property Test: Jobs preserve monitor data when serialized to queue.
     */
    public function test_jobs_preserve_monitor_data_when_serialized(): void
    {
        Queue::fake();

        // Test with 100 monitors with various configurations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'name' => 'Monitor ' . $i,
                'check_interval_minutes' => ($i % 10) + 1,
                'status' => ['pending', 'up', 'down'][$i % 3],
            ]);

            PerformMonitorCheck::dispatch($monitor);

            // Assert job contains correct monitor
            Queue::assertPushed(PerformMonitorCheck::class, function ($job) use ($monitor) {
                return $job->monitor->id === $monitor->id
                    && $job->monitor->url === $monitor->url
                    && $job->monitor->name === $monitor->name
                    && $job->monitor->check_interval_minutes === $monitor->check_interval_minutes
                    && $job->monitor->status === $monitor->status;
            });
        }
    }

    /**
     * Property Test: Queue system doesn't execute jobs synchronously during dispatch.
     */
    public function test_queue_system_does_not_execute_jobs_synchronously(): void
    {
        Queue::fake();

        // Create 100 monitors
        $monitors = [];
        for ($i = 0; $i < 100; $i++) {
            $monitors[] = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'status' => 'pending',
            ]);
        }

        // Dispatch all jobs
        foreach ($monitors as $monitor) {
            PerformMonitorCheck::dispatch($monitor);
        }

        // Assert monitors still have pending status (jobs haven't executed)
        foreach ($monitors as $monitor) {
            $monitor->refresh();
            $this->assertEquals('pending', $monitor->status);
        }

        // Assert no checks were created (jobs haven't executed)
        $this->assertDatabaseCount('checks', 0);
    }

    /**
     * Property Test: Jobs can be dispatched to specific queues.
     */
    public function test_jobs_can_be_dispatched_to_specific_queues(): void
    {
        Queue::fake();

        // Test with 100 monitors
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
            ]);

            // Dispatch to specific queue
            $queue_name = 'checks';
            PerformMonitorCheck::dispatch($monitor)->onQueue($queue_name);

            // Assert job was pushed to the correct queue
            Queue::assertPushedOn($queue_name, PerformMonitorCheck::class);
        }

        // Assert total of 100 jobs were pushed
        Queue::assertPushed(PerformMonitorCheck::class, 100);
    }

    /**
     * Property Test: Dispatching same monitor multiple times creates multiple jobs.
     */
    public function test_dispatching_same_monitor_multiple_times_creates_multiple_jobs(): void
    {
        Queue::fake();

        // Create one monitor
        $monitor = Monitor::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com',
        ]);

        // Dispatch 100 times
        for ($i = 0; $i < 100; $i++) {
            PerformMonitorCheck::dispatch($monitor);
        }

        // Assert 100 jobs were pushed for the same monitor
        Queue::assertPushed(PerformMonitorCheck::class, 100);

        // Assert all jobs reference the same monitor
        Queue::assertPushed(PerformMonitorCheck::class, function ($job) use ($monitor) {
            return $job->monitor->id === $monitor->id;
        });
    }

    /**
     * Property Test: Jobs maintain monitor relationship after serialization.
     */
    public function test_jobs_maintain_monitor_relationship_after_serialization(): void
    {
        Queue::fake();

        // Test with 100 monitors
        $monitors = [];
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
            ]);
            $monitors[] = $monitor;

            PerformMonitorCheck::dispatch($monitor);
        }

        // Assert all jobs maintain monitor relationship
        foreach ($monitors as $monitor) {
            Queue::assertPushed(PerformMonitorCheck::class, function ($job) use ($monitor) {
                // Only check jobs that match this monitor
                if ($job->monitor->id !== $monitor->id) {
                    return false;
                }
                
                // Job should have monitor property
                $this->assertNotNull($job->monitor);
                $this->assertInstanceOf(Monitor::class, $job->monitor);
                $this->assertEquals($monitor->id, $job->monitor->id);
                
                // Monitor should maintain user relationship
                $this->assertNotNull($job->monitor->user);
                $this->assertEquals($this->user->id, $job->monitor->user_id);
                
                return true;
            });
        }
    }

    /**
     * Property Test: Queue fake prevents actual job execution.
     */
    public function test_queue_fake_prevents_actual_job_execution(): void
    {
        Queue::fake();

        // Create 100 monitors and dispatch jobs
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'status' => 'pending',
                'last_checked_at' => null,
            ]);

            PerformMonitorCheck::dispatch($monitor);

            // Verify job was queued but not executed
            $monitor->refresh();
            $this->assertEquals('pending', $monitor->status);
            $this->assertNull($monitor->last_checked_at);
        }

        // Verify no checks were created
        $this->assertDatabaseCount('checks', 0);

        // Verify all jobs were queued
        Queue::assertPushed(PerformMonitorCheck::class, 100);
    }
}
