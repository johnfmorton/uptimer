<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Check;
use App\Models\Monitor;
use App\Models\User;
use App\Services\CheckService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckServiceTest extends TestCase
{
    use RefreshDatabase;

    private CheckService $checkService;
    private User $user;
    private Monitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->checkService = new CheckService();
        $this->user = User::factory()->create();
        $this->monitor = Monitor::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com',
            'status' => 'pending',
        ]);
    }

    public function test_perform_check_marks_monitor_up_on_2xx_response(): void
    {
        Http::fake([
            'example.com' => Http::response('OK', 200),
        ]);

        $check = $this->checkService->performCheck($this->monitor);

        $this->assertEquals('success', $check->status);
        $this->assertEquals(200, $check->status_code);
        $this->assertNull($check->error_message);
        $this->assertNotNull($check->response_time_ms);
        
        $this->monitor->refresh();
        $this->assertEquals('up', $this->monitor->status);
        $this->assertNotNull($this->monitor->last_checked_at);
    }

    public function test_perform_check_marks_monitor_down_on_4xx_response(): void
    {
        Http::fake([
            'example.com' => Http::response('Not Found', 404),
        ]);

        $check = $this->checkService->performCheck($this->monitor);

        $this->assertEquals('failed', $check->status);
        $this->assertEquals(404, $check->status_code);
        $this->assertEquals('HTTP 404 response received', $check->error_message);
        
        $this->monitor->refresh();
        $this->assertEquals('down', $this->monitor->status);
    }

    public function test_perform_check_marks_monitor_down_on_5xx_response(): void
    {
        Http::fake([
            'example.com' => Http::response('Server Error', 500),
        ]);

        $check = $this->checkService->performCheck($this->monitor);

        $this->assertEquals('failed', $check->status);
        $this->assertEquals(500, $check->status_code);
        $this->assertEquals('HTTP 500 response received', $check->error_message);
        
        $this->monitor->refresh();
        $this->assertEquals('down', $this->monitor->status);
    }

    public function test_perform_check_handles_connection_timeout(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out after 30 seconds');
        });

        $check = $this->checkService->performCheck($this->monitor);

        $this->assertEquals('failed', $check->status);
        $this->assertNull($check->status_code);
        $this->assertNull($check->response_time_ms);
        $this->assertStringContainsString('timeout', strtolower($check->error_message));
        
        $this->monitor->refresh();
        $this->assertEquals('down', $this->monitor->status);
    }

    public function test_perform_check_handles_dns_resolution_failure(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Could not resolve host');
        });

        $check = $this->checkService->performCheck($this->monitor);

        $this->assertEquals('failed', $check->status);
        $this->assertStringContainsString('resolve', strtolower($check->error_message));
        
        $this->monitor->refresh();
        $this->assertEquals('down', $this->monitor->status);
    }

    public function test_perform_check_updates_last_checked_at(): void
    {
        Http::fake([
            'example.com' => Http::response('OK', 200),
        ]);

        $before = Carbon::now()->subSecond();
        $this->checkService->performCheck($this->monitor);
        $after = Carbon::now()->addSecond();

        $this->monitor->refresh();
        $this->assertNotNull($this->monitor->last_checked_at);
        $this->assertTrue($this->monitor->last_checked_at->between($before, $after));
    }

    public function test_perform_check_updates_last_status_change_at_on_status_change(): void
    {
        // Set monitor to 'up' status first
        $this->monitor->update(['status' => 'up']);
        
        Http::fake([
            'example.com' => Http::response('Server Error', 500),
        ]);

        $before = Carbon::now()->subSecond();
        $this->checkService->performCheck($this->monitor);
        $after = Carbon::now()->addSecond();

        $this->monitor->refresh();
        $this->assertEquals('down', $this->monitor->status);
        $this->assertNotNull($this->monitor->last_status_change_at);
        $this->assertTrue($this->monitor->last_status_change_at->between($before, $after));
    }

    public function test_perform_check_does_not_update_last_status_change_at_when_status_unchanged(): void
    {
        // Set monitor to 'up' status with a specific timestamp
        $original_timestamp = Carbon::now()->subHours(2);
        $this->monitor->update([
            'status' => 'up',
            'last_status_change_at' => $original_timestamp,
        ]);
        
        Http::fake([
            'example.com' => Http::response('OK', 200),
        ]);

        $this->checkService->performCheck($this->monitor);

        $this->monitor->refresh();
        $this->assertEquals('up', $this->monitor->status);
        $this->assertEquals($original_timestamp->timestamp, $this->monitor->last_status_change_at->timestamp);
    }

    public function test_perform_check_does_not_trigger_notification_on_first_check(): void
    {
        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->never())->method('notifyStatusChange');
        
        $checkService = new CheckService($notificationService);
        
        Http::fake([
            'example.com' => Http::response('Server Error', 500),
        ]);

        $checkService->performCheck($this->monitor);
    }

    public function test_perform_check_triggers_notification_on_status_change(): void
    {
        $this->monitor->update(['status' => 'up']);
        
        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->once())
            ->method('notifyStatusChange')
            ->with(
                $this->callback(fn($m) => $m->id === $this->monitor->id),
                'up',
                'down'
            );
        
        $checkService = new CheckService($notificationService);
        
        Http::fake([
            'example.com' => Http::response('Server Error', 500),
        ]);

        $checkService->performCheck($this->monitor);
    }

    public function test_perform_check_does_not_trigger_notification_when_status_unchanged(): void
    {
        $this->monitor->update(['status' => 'up']);
        
        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->never())->method('notifyStatusChange');
        
        $checkService = new CheckService($notificationService);
        
        Http::fake([
            'example.com' => Http::response('OK', 200),
        ]);

        $checkService->performCheck($this->monitor);
    }

    public function test_calculate_uptime_returns_100_percent_for_all_successful_checks(): void
    {
        $now = Carbon::now();
        
        // Create 10 successful checks
        for ($i = 0; $i < 10; $i++) {
            Check::factory()->create([
                'monitor_id' => $this->monitor->id,
                'status' => 'success',
                'checked_at' => $now->copy()->subHours($i),
            ]);
        }

        $uptime = $this->checkService->calculateUptime($this->monitor, 24);

        $this->assertEquals(100.0, $uptime);
    }

    public function test_calculate_uptime_returns_0_percent_for_all_failed_checks(): void
    {
        $now = Carbon::now();
        
        // Create 10 failed checks
        for ($i = 0; $i < 10; $i++) {
            Check::factory()->create([
                'monitor_id' => $this->monitor->id,
                'status' => 'failed',
                'checked_at' => $now->copy()->subHours($i),
            ]);
        }

        $uptime = $this->checkService->calculateUptime($this->monitor, 24);

        $this->assertEquals(0.0, $uptime);
    }

    public function test_calculate_uptime_returns_correct_percentage_for_mixed_checks(): void
    {
        $now = Carbon::now();
        
        // Create 7 successful and 3 failed checks (70% uptime)
        for ($i = 0; $i < 7; $i++) {
            Check::factory()->create([
                'monitor_id' => $this->monitor->id,
                'status' => 'success',
                'checked_at' => $now->copy()->subHours($i),
            ]);
        }
        
        for ($i = 7; $i < 10; $i++) {
            Check::factory()->create([
                'monitor_id' => $this->monitor->id,
                'status' => 'failed',
                'checked_at' => $now->copy()->subHours($i),
            ]);
        }

        $uptime = $this->checkService->calculateUptime($this->monitor, 24);

        $this->assertEquals(70.0, $uptime);
    }

    public function test_calculate_uptime_returns_null_when_no_checks_exist(): void
    {
        $uptime = $this->checkService->calculateUptime($this->monitor, 24);

        $this->assertNull($uptime);
    }

    public function test_calculate_uptime_only_includes_checks_within_time_period(): void
    {
        $now = Carbon::now();
        
        // Create checks within the last 24 hours (should be included)
        for ($i = 0; $i < 5; $i++) {
            Check::factory()->create([
                'monitor_id' => $this->monitor->id,
                'status' => 'success',
                'checked_at' => $now->copy()->subHours($i),
            ]);
        }
        
        // Create checks older than 24 hours (should be excluded)
        for ($i = 0; $i < 5; $i++) {
            Check::factory()->create([
                'monitor_id' => $this->monitor->id,
                'status' => 'failed',
                'checked_at' => $now->copy()->subHours(25 + $i),
            ]);
        }

        $uptime = $this->checkService->calculateUptime($this->monitor, 24);

        // Should only count the 5 successful checks within 24 hours
        $this->assertEquals(100.0, $uptime);
    }
}
