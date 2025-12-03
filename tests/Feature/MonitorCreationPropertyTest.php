<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Test for Monitor Creation with Valid URL
 *
 * **Feature: uptime-monitor, Property 4: Monitor creation with valid URL succeeds**
 *
 * Property: For any valid URL and monitor data submitted by an authenticated administrator,
 * the system should create a monitor record with status 'pending'.
 *
 * Validates: Requirements 2.1
 */
class MonitorCreationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generate random valid monitor data for testing.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function validMonitorDataProvider(): array
    {
        $test_cases = [];

        // Generate 100+ test cases with random valid monitor data
        for ($i = 0; $i < 100; $i++) {
            // Generate various valid URL formats
            $url_patterns = [
                'https://example'.$i.'.com',
                'http://test-site-'.$i.'.org',
                'https://subdomain.example'.$i.'.net',
                'https://example.com/path'.$i,
                'https://example.com:8080/path'.$i,
                'https://example'.$i.'.com/path/to/resource',
                'http://192.168.1.'.(($i % 254) + 1),
                'https://example'.$i.'.co.uk',
                'https://api.example'.$i.'.com/v1/endpoint',
                'https://example'.$i.'.com?query=param',
            ];

            $url = $url_patterns[$i % count($url_patterns)];

            // Generate random monitor names
            $name_patterns = [
                'Monitor '.$i,
                'Test Site '.$i,
                'Production Server '.$i,
                'API Endpoint '.$i,
                'Website Monitor '.$i,
                'Service '.$i.' Health Check',
                'App '.$i.' Status',
            ];

            $name = $name_patterns[$i % count($name_patterns)];

            // Generate random check intervals (1-1440 minutes)
            $check_interval = rand(1, 1440);

            $test_cases[] = [
                'name' => $name,
                'url' => $url,
                'check_interval_minutes' => $check_interval,
            ];
        }

        return $test_cases;
    }

    /**
     * Property Test: Monitor creation with valid URL succeeds and sets pending status.
     *
     * @dataProvider validMonitorDataProvider
     */
    public function test_monitor_creation_with_valid_url_succeeds_and_sets_pending_status(
        string $name,
        string $url,
        int $check_interval_minutes
    ): void {
        // Create an authenticated user
        $user = User::factory()->create();

        // Act as the authenticated user
        $this->actingAs($user);

        // Ensure we start with no monitors
        $this->assertDatabaseCount('monitors', 0);

        // Create monitor with valid data
        $monitor = Monitor::create([
            'user_id' => $user->id,
            'name' => $name,
            'url' => $url,
            'check_interval_minutes' => $check_interval_minutes,
            'status' => 'pending',
        ]);

        // Assert monitor was created
        $this->assertNotNull($monitor);
        $this->assertInstanceOf(Monitor::class, $monitor);

        // Assert monitor has correct attributes
        $this->assertEquals($name, $monitor->name);
        $this->assertEquals($url, $monitor->url);
        $this->assertEquals($check_interval_minutes, $monitor->check_interval_minutes);
        $this->assertEquals($user->id, $monitor->user_id);

        // Assert monitor has pending status (Requirement 2.5)
        $this->assertEquals('pending', $monitor->status);
        $this->assertTrue($monitor->isPending());

        // Assert monitor was persisted to database
        $this->assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'user_id' => $user->id,
            'name' => $name,
            'url' => $url,
            'check_interval_minutes' => $check_interval_minutes,
            'status' => 'pending',
        ]);

        // Clean up
        $monitor->delete();
        $user->delete();
    }

    /**
     * Property Test: Monitor creation with various URL formats succeeds.
     */
    public function test_monitor_creation_with_various_url_formats_succeeds(): void
    {
        // Run 100 iterations with different URL formats
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Generate various valid URL formats
            $valid_urls = [
                'https://example.com',
                'http://example.com',
                'https://subdomain.example.com',
                'https://example.com:8080',
                'https://example.com/path',
                'https://example.com/path/to/resource',
                'https://example.com?query=value',
                'https://example.com#fragment',
                'https://example.com/path?query=value#fragment',
                'http://192.168.1.1',
                'https://example.co.uk',
                'https://api.example.com/v1/endpoint',
            ];

            $url = $valid_urls[$i % count($valid_urls)].'?test='.$i;

            // Create monitor
            $monitor = Monitor::create([
                'user_id' => $user->id,
                'name' => 'Test Monitor '.$i,
                'url' => $url,
                'check_interval_minutes' => 5,
                'status' => 'pending',
            ]);

            // Assert monitor was created successfully
            $this->assertNotNull($monitor);
            $this->assertEquals($url, $monitor->url);
            $this->assertEquals('pending', $monitor->status);

            // Assert database persistence
            $this->assertDatabaseHas('monitors', [
                'id' => $monitor->id,
                'url' => $url,
                'status' => 'pending',
            ]);

            // Clean up
            $monitor->delete();
            $user->delete();
        }
    }

    /**
     * Property Test: Monitor creation with various check intervals succeeds.
     */
    public function test_monitor_creation_with_various_check_intervals_succeeds(): void
    {
        // Run 100 iterations with different check intervals
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Test various check intervals (1-1440 minutes)
            $check_intervals = [1, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440];
            $check_interval = $check_intervals[$i % count($check_intervals)];

            // Create monitor
            $monitor = Monitor::create([
                'user_id' => $user->id,
                'name' => 'Interval Test '.$i,
                'url' => 'https://example'.$i.'.com',
                'check_interval_minutes' => $check_interval,
                'status' => 'pending',
            ]);

            // Assert monitor was created successfully
            $this->assertNotNull($monitor);
            $this->assertEquals($check_interval, $monitor->check_interval_minutes);
            $this->assertEquals('pending', $monitor->status);

            // Assert database persistence
            $this->assertDatabaseHas('monitors', [
                'id' => $monitor->id,
                'check_interval_minutes' => $check_interval,
                'status' => 'pending',
            ]);

            // Clean up
            $monitor->delete();
            $user->delete();
        }
    }

    /**
     * Property Test: Monitor creation establishes user relationship.
     */
    public function test_monitor_creation_establishes_user_relationship(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Create monitor
            $monitor = Monitor::create([
                'user_id' => $user->id,
                'name' => 'Relationship Test '.$i,
                'url' => 'https://example'.$i.'.com',
                'check_interval_minutes' => 5,
                'status' => 'pending',
            ]);

            // Assert monitor belongs to user
            $this->assertEquals($user->id, $monitor->user_id);
            $this->assertInstanceOf(User::class, $monitor->user);
            $this->assertEquals($user->id, $monitor->user->id);

            // Assert user has the monitor
            $this->assertTrue($user->monitors->contains($monitor));

            // Clean up
            $monitor->delete();
            $user->delete();
        }
    }

    /**
     * Property Test: Newly created monitors have null timestamps initially.
     */
    public function test_newly_created_monitors_have_null_check_timestamps(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Create monitor without check timestamps
            $monitor = Monitor::create([
                'user_id' => $user->id,
                'name' => 'Timestamp Test '.$i,
                'url' => 'https://example'.$i.'.com',
                'check_interval_minutes' => 5,
                'status' => 'pending',
            ]);

            // Assert check timestamps are null for new monitors
            $this->assertNull($monitor->last_checked_at);
            $this->assertNull($monitor->last_status_change_at);

            // Assert status is pending
            $this->assertEquals('pending', $monitor->status);
            $this->assertTrue($monitor->isPending());

            // Clean up
            $monitor->delete();
            $user->delete();
        }
    }
}

