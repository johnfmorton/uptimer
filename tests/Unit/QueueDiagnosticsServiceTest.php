<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\TestQueueJob;
use App\Services\QueueDiagnosticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueDiagnosticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private QueueDiagnosticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QueueDiagnosticsService;
    }

    public function test_get_pending_jobs_count_returns_zero_when_no_jobs(): void
    {
        $count = $this->service->getPendingJobsCount();

        $this->assertEquals(0, $count);
    }

    public function test_get_pending_jobs_count_returns_correct_count(): void
    {
        // Insert test jobs directly into the jobs table
        DB::table('jobs')->insert([
            [
                'queue' => 'default',
                'payload' => json_encode(['job' => 'test']),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->timestamp,
                'created_at' => now()->timestamp,
            ],
            [
                'queue' => 'default',
                'payload' => json_encode(['job' => 'test2']),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->timestamp,
                'created_at' => now()->timestamp,
            ],
        ]);

        $count = $this->service->getPendingJobsCount();

        $this->assertEquals(2, $count);
    }

    public function test_get_failed_jobs_count_returns_zero_when_no_failed_jobs(): void
    {
        $count = $this->service->getFailedJobsCount();

        $this->assertEquals(0, $count);
    }

    public function test_get_failed_jobs_count_only_counts_last_hour(): void
    {
        // Insert a failed job from 2 hours ago
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-1',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['job' => 'test']),
            'exception' => 'Test exception',
            'failed_at' => now()->subHours(2),
        ]);

        // Insert a failed job from 30 minutes ago
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-2',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['job' => 'test2']),
            'exception' => 'Test exception',
            'failed_at' => now()->subMinutes(30),
        ]);

        $count = $this->service->getFailedJobsCount();

        $this->assertEquals(1, $count);
    }

    public function test_get_stuck_jobs_count_returns_zero_when_no_stuck_jobs(): void
    {
        $count = $this->service->getStuckJobsCount();

        $this->assertEquals(0, $count);
    }

    public function test_get_stuck_jobs_count_identifies_old_pending_jobs(): void
    {
        // Insert a recent job (not stuck)
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['job' => 'recent']),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->subMinutes(2)->timestamp,
        ]);

        // Insert an old job (stuck)
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['job' => 'stuck']),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->subMinutes(10)->timestamp,
        ]);

        $count = $this->service->getStuckJobsCount();

        $this->assertEquals(1, $count);
    }

    public function test_is_queue_worker_running_returns_true_when_no_stuck_jobs(): void
    {
        $running = $this->service->isQueueWorkerRunning();

        $this->assertTrue($running);
    }

    public function test_is_queue_worker_running_returns_false_when_stuck_jobs_exist(): void
    {
        // Insert a stuck job
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['job' => 'stuck']),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->subMinutes(10)->timestamp,
        ]);

        $running = $this->service->isQueueWorkerRunning();

        $this->assertFalse($running);
    }

    public function test_is_scheduler_running_returns_false_when_no_heartbeat(): void
    {
        $running = $this->service->isSchedulerRunning();

        $this->assertFalse($running);
    }

    public function test_is_scheduler_running_returns_true_when_recent_heartbeat(): void
    {
        Cache::put('scheduler:heartbeat', now()->timestamp, 90);

        $running = $this->service->isSchedulerRunning();

        $this->assertTrue($running);
    }

    public function test_is_scheduler_running_returns_false_when_stale_heartbeat(): void
    {
        Cache::put('scheduler:heartbeat', now()->subSeconds(100)->timestamp, 90);

        $running = $this->service->isSchedulerRunning();

        $this->assertFalse($running);
    }

    public function test_dispatch_test_job_dispatches_job_to_queue(): void
    {
        Queue::fake();

        $this->service->dispatchTestJob();

        Queue::assertPushed(TestQueueJob::class);
    }

    public function test_get_queue_diagnostics_returns_complete_array(): void
    {
        Cache::put('scheduler:heartbeat', now()->timestamp, 90);

        $diagnostics = $this->service->getQueueDiagnostics();

        $this->assertIsArray($diagnostics);
        $this->assertArrayHasKey('pending_jobs', $diagnostics);
        $this->assertArrayHasKey('failed_jobs_last_hour', $diagnostics);
        $this->assertArrayHasKey('stuck_jobs', $diagnostics);
        $this->assertArrayHasKey('queue_worker_running', $diagnostics);
        $this->assertArrayHasKey('scheduler_running', $diagnostics);
        $this->assertArrayHasKey('has_issues', $diagnostics);
    }

    public function test_get_queue_diagnostics_has_issues_true_when_worker_not_running(): void
    {
        // Insert a stuck job
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['job' => 'stuck']),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->subMinutes(10)->timestamp,
        ]);

        Cache::put('scheduler:heartbeat', now()->timestamp, 90);

        $diagnostics = $this->service->getQueueDiagnostics();

        $this->assertTrue($diagnostics['has_issues']);
    }

    public function test_get_queue_diagnostics_has_issues_true_when_scheduler_not_running(): void
    {
        $diagnostics = $this->service->getQueueDiagnostics();

        $this->assertTrue($diagnostics['has_issues']);
    }

    public function test_get_queue_diagnostics_has_issues_false_when_all_healthy(): void
    {
        Cache::put('scheduler:heartbeat', now()->timestamp, 90);

        $diagnostics = $this->service->getQueueDiagnostics();

        $this->assertFalse($diagnostics['has_issues']);
    }
}
