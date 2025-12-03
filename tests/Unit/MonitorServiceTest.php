<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Check;
use App\Models\Monitor;
use App\Models\User;
use App\Services\MonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonitorServiceTest extends TestCase
{
    use RefreshDatabase;

    private MonitorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MonitorService();
    }

    public function test_create_monitor_creates_monitor_with_pending_status(): void
    {
        $user = User::factory()->create();
        
        $data = [
            'name' => 'Test Monitor',
            'url' => 'https://example.com',
            'check_interval_minutes' => 5,
        ];
        
        $monitor = $this->service->createMonitor($user, $data);
        
        $this->assertInstanceOf(Monitor::class, $monitor);
        $this->assertEquals('Test Monitor', $monitor->name);
        $this->assertEquals('https://example.com', $monitor->url);
        $this->assertEquals(5, $monitor->check_interval_minutes);
        $this->assertEquals('pending', $monitor->status);
        $this->assertEquals($user->id, $monitor->user_id);
    }

    public function test_update_monitor_updates_monitor_details(): void
    {
        $monitor = Monitor::factory()->create([
            'name' => 'Old Name',
            'url' => 'https://old-url.com',
            'check_interval_minutes' => 5,
        ]);
        
        $updated_data = [
            'name' => 'New Name',
            'url' => 'https://new-url.com',
            'check_interval_minutes' => 10,
        ];
        
        $updated_monitor = $this->service->updateMonitor($monitor, $updated_data);
        
        $this->assertEquals('New Name', $updated_monitor->name);
        $this->assertEquals('https://new-url.com', $updated_monitor->url);
        $this->assertEquals(10, $updated_monitor->check_interval_minutes);
    }

    public function test_delete_monitor_removes_monitor(): void
    {
        $monitor = Monitor::factory()->create();
        $monitor_id = $monitor->id;
        
        $result = $this->service->deleteMonitor($monitor);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('monitors', ['id' => $monitor_id]);
    }

    public function test_get_all_monitors_for_user_returns_user_monitors(): void
    {
        $user = User::factory()->create();
        $other_user = User::factory()->create();
        
        Monitor::factory()->count(3)->create(['user_id' => $user->id]);
        Monitor::factory()->count(2)->create(['user_id' => $other_user->id]);
        
        $monitors = $this->service->getAllMonitorsForUser($user);
        
        $this->assertCount(3, $monitors);
        $this->assertTrue($monitors->every(fn($monitor) => $monitor->user_id === $user->id));
    }

    public function test_get_monitor_with_stats_returns_monitor_and_uptime_stats(): void
    {
        $monitor = Monitor::factory()->create();
        
        // Create checks for the last 24 hours (10 successful, 2 failed)
        Check::factory()->count(10)->create([
            'monitor_id' => $monitor->id,
            'status' => 'success',
            'checked_at' => Carbon::now()->subHours(12),
        ]);
        
        Check::factory()->count(2)->create([
            'monitor_id' => $monitor->id,
            'status' => 'failed',
            'checked_at' => Carbon::now()->subHours(12),
        ]);
        
        $result = $this->service->getMonitorWithStats($monitor);
        
        $this->assertArrayHasKey('monitor', $result);
        $this->assertArrayHasKey('uptime_24h', $result);
        $this->assertArrayHasKey('uptime_7d', $result);
        $this->assertArrayHasKey('uptime_30d', $result);
        
        $this->assertInstanceOf(Monitor::class, $result['monitor']);
        $this->assertEquals($monitor->id, $result['monitor']->id);
        
        // 10 successful out of 12 total = 83.33%
        $this->assertEqualsWithDelta(83.33, $result['uptime_24h'], 0.01);
    }

    public function test_get_monitor_with_stats_returns_null_for_periods_without_checks(): void
    {
        $monitor = Monitor::factory()->create();
        
        // Create checks only for the last 24 hours (but older than 7 days)
        Check::factory()->count(5)->create([
            'monitor_id' => $monitor->id,
            'status' => 'success',
            'checked_at' => Carbon::now()->subDays(10),
        ]);
        
        $result = $this->service->getMonitorWithStats($monitor);
        
        $this->assertNull($result['uptime_24h']); // No checks in last 24 hours
        $this->assertNull($result['uptime_7d']); // No checks in last 7 days
        $this->assertNotNull($result['uptime_30d']); // Checks exist in 30 day period
    }

    public function test_calculate_uptime_returns_100_percent_for_all_successful_checks(): void
    {
        $monitor = Monitor::factory()->create();
        
        Check::factory()->count(10)->create([
            'monitor_id' => $monitor->id,
            'status' => 'success',
            'checked_at' => Carbon::now()->subHours(12),
        ]);
        
        $result = $this->service->getMonitorWithStats($monitor);
        
        $this->assertEquals(100.0, $result['uptime_24h']);
    }

    public function test_calculate_uptime_returns_0_percent_for_all_failed_checks(): void
    {
        $monitor = Monitor::factory()->create();
        
        Check::factory()->count(10)->create([
            'monitor_id' => $monitor->id,
            'status' => 'failed',
            'checked_at' => Carbon::now()->subHours(12),
        ]);
        
        $result = $this->service->getMonitorWithStats($monitor);
        
        $this->assertEquals(0.0, $result['uptime_24h']);
    }
}

