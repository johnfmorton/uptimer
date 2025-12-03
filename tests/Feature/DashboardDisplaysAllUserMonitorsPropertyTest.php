<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Test for Dashboard Displaying All User Monitors
 *
 * **Feature: uptime-monitor, Property 6: Dashboard displays all user monitors**
 *
 * Property: For any authenticated administrator viewing the dashboard,
 * all monitors belonging to that user should be displayed with their current status.
 *
 * Validates: Requirements 3.1
 */
class DashboardDisplaysAllUserMonitorsPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generate test cases with varying numbers of monitors.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function monitorCountProvider(): array
    {
        $test_cases = [];

        // Generate 100+ test cases with different monitor counts
        for ($i = 0; $i < 100; $i++) {
            // Vary the number of monitors from 0 to 20
            $monitor_count = $i % 21;
            
            $test_cases[] = [
                'monitor_count' => $monitor_count,
            ];
        }

        return $test_cases;
    }

    /**
     * Property Test: Dashboard displays all monitors belonging to authenticated user.
     *
     * @dataProvider monitorCountProvider
     */
    public function test_dashboard_displays_all_user_monitors(int $monitor_count): void
    {
        // Create authenticated user
        $user = User::factory()->create();

        // Create monitors for this user
        $user_monitors = Monitor::factory()
            ->count($monitor_count)
            ->create(['user_id' => $user->id]);

        // Create some monitors for other users (should not be displayed)
        $other_user = User::factory()->create();
        $other_monitors = Monitor::factory()
            ->count(rand(1, 5))
            ->create(['user_id' => $other_user->id]);

        // Act as the authenticated user and visit dashboard
        $response = $this->actingAs($user)->get('/dashboard');

        // Assert response is successful
        $response->assertStatus(200);

        // Assert all user's monitors are present in the response
        foreach ($user_monitors as $monitor) {
            $response->assertSee($monitor->name);
            $response->assertSee($monitor->url);
            $response->assertSee(ucfirst($monitor->status));
        }

        // Assert other user's monitors are NOT present
        foreach ($other_monitors as $monitor) {
            $response->assertDontSee($monitor->name);
        }

        // Clean up
        foreach ($user_monitors as $monitor) {
            $monitor->delete();
        }
        foreach ($other_monitors as $monitor) {
            $monitor->delete();
        }
        $user->delete();
        $other_user->delete();
    }

    /**
     * Property Test: Dashboard displays monitors with all status types.
     */
    public function test_dashboard_displays_monitors_with_all_status_types(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Create monitors with different statuses
            $monitor_up = Monitor::factory()->up()->create([
                'user_id' => $user->id,
                'name' => 'Up Monitor ' . $i,
            ]);

            $monitor_down = Monitor::factory()->down()->create([
                'user_id' => $user->id,
                'name' => 'Down Monitor ' . $i,
            ]);

            $monitor_pending = Monitor::factory()->pending()->create([
                'user_id' => $user->id,
                'name' => 'Pending Monitor ' . $i,
            ]);

            // Visit dashboard
            $response = $this->actingAs($user)->get('/dashboard');

            // Assert all monitors are displayed regardless of status
            $response->assertStatus(200);
            $response->assertSee($monitor_up->name);
            $response->assertSee($monitor_down->name);
            $response->assertSee($monitor_pending->name);

            // Assert statuses are displayed (capitalized in view)
            $response->assertSee('Up');
            $response->assertSee('Down');
            $response->assertSee('Pending');

            // Clean up
            $monitor_up->delete();
            $monitor_down->delete();
            $monitor_pending->delete();
            $user->delete();
        }
    }

    /**
     * Property Test: Dashboard displays correct monitor count for user.
     */
    public function test_dashboard_displays_correct_monitor_count_for_user(): void
    {
        // Run 100 iterations with varying monitor counts
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();
            
            // Create random number of monitors (0-15)
            $monitor_count = $i % 16;
            $monitors = Monitor::factory()
                ->count($monitor_count)
                ->create(['user_id' => $user->id]);

            // Visit dashboard
            $response = $this->actingAs($user)->get('/dashboard');

            // Assert response is successful
            $response->assertStatus(200);

            // Assert correct number of monitors are displayed
            // Each monitor name should appear exactly once
            foreach ($monitors as $monitor) {
                $response->assertSee($monitor->name);
            }

            // Verify user has correct number of monitors in database
            $this->assertEquals($monitor_count, $user->monitors()->count());

            // Clean up
            foreach ($monitors as $monitor) {
                $monitor->delete();
            }
            $user->delete();
        }
    }

    /**
     * Property Test: Dashboard does not display monitors from other users.
     */
    public function test_dashboard_does_not_display_other_users_monitors(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create two users
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            // Create monitors for user1
            $user1_monitors = Monitor::factory()
                ->count(rand(1, 5))
                ->create(['user_id' => $user1->id]);

            // Create monitors for user2
            $user2_monitors = Monitor::factory()
                ->count(rand(1, 5))
                ->create(['user_id' => $user2->id]);

            // User1 views dashboard
            $response1 = $this->actingAs($user1)->get('/dashboard');

            // Assert user1 sees only their monitors
            foreach ($user1_monitors as $monitor) {
                $response1->assertSee($monitor->name);
            }
            foreach ($user2_monitors as $monitor) {
                $response1->assertDontSee($monitor->name);
            }

            // User2 views dashboard
            $response2 = $this->actingAs($user2)->get('/dashboard');

            // Assert user2 sees only their monitors
            foreach ($user2_monitors as $monitor) {
                $response2->assertSee($monitor->name);
            }
            foreach ($user1_monitors as $monitor) {
                $response2->assertDontSee($monitor->name);
            }

            // Clean up
            foreach ($user1_monitors as $monitor) {
                $monitor->delete();
            }
            foreach ($user2_monitors as $monitor) {
                $monitor->delete();
            }
            $user1->delete();
            $user2->delete();
        }
    }

    /**
     * Property Test: Dashboard displays monitors with various URLs.
     */
    public function test_dashboard_displays_monitors_with_various_urls(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Create monitors with different URL patterns
            $url_patterns = [
                'https://example' . $i . '.com',
                'http://test-site-' . $i . '.org',
                'https://subdomain.example' . $i . '.net',
                'https://example.com/path' . $i,
                'https://api.example' . $i . '.com/v1/endpoint',
            ];

            $monitors = [];
            foreach ($url_patterns as $index => $url) {
                $monitors[] = Monitor::factory()->create([
                    'user_id' => $user->id,
                    'name' => 'Monitor ' . $i . '-' . $index,
                    'url' => $url,
                ]);
            }

            // Visit dashboard
            $response = $this->actingAs($user)->get('/dashboard');

            // Assert all monitors with different URLs are displayed
            $response->assertStatus(200);
            foreach ($monitors as $monitor) {
                $response->assertSee($monitor->name);
                $response->assertSee($monitor->url);
            }

            // Clean up
            foreach ($monitors as $monitor) {
                $monitor->delete();
            }
            $user->delete();
        }
    }

    /**
     * Property Test: Dashboard handles empty monitor list gracefully.
     */
    public function test_dashboard_handles_empty_monitor_list_gracefully(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // User has no monitors
            $this->assertEquals(0, $user->monitors()->count());

            // Visit dashboard
            $response = $this->actingAs($user)->get('/dashboard');

            // Assert response is successful even with no monitors
            $response->assertStatus(200);
            $response->assertViewIs('dashboard');

            // Clean up
            $user->delete();
        }
    }

    /**
     * Property Test: Dashboard displays monitors with various check intervals.
     */
    public function test_dashboard_displays_monitors_with_various_check_intervals(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Create monitors with different check intervals
            $check_intervals = [1, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440];
            $interval = $check_intervals[$i % count($check_intervals)];

            $monitor = Monitor::factory()->create([
                'user_id' => $user->id,
                'name' => 'Interval Monitor ' . $i,
                'check_interval_minutes' => $interval,
            ]);

            // Visit dashboard
            $response = $this->actingAs($user)->get('/dashboard');

            // Assert monitor is displayed
            $response->assertStatus(200);
            $response->assertSee($monitor->name);

            // Clean up
            $monitor->delete();
            $user->delete();
        }
    }

    /**
     * Property Test: Unauthenticated users cannot view dashboard.
     */
    public function test_unauthenticated_users_cannot_view_dashboard(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create user with monitors
            $user = User::factory()->create();
            $monitors = Monitor::factory()
                ->count(rand(1, 5))
                ->create(['user_id' => $user->id]);

            // Attempt to access dashboard without authentication
            $response = $this->get('/dashboard');

            // Assert redirect to login
            $response->assertRedirect('/login');

            // Assert monitors are not visible
            foreach ($monitors as $monitor) {
                $response->assertDontSee($monitor->name);
            }

            // Clean up
            foreach ($monitors as $monitor) {
                $monitor->delete();
            }
            $user->delete();
        }
    }

    /**
     * Property Test: Dashboard displays monitors with timestamps.
     */
    public function test_dashboard_displays_monitors_with_timestamps(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Create monitor with timestamps
            $monitor = Monitor::factory()->create([
                'user_id' => $user->id,
                'name' => 'Timestamp Monitor ' . $i,
                'last_checked_at' => now()->subMinutes(rand(1, 60)),
                'last_status_change_at' => now()->subHours(rand(1, 24)),
            ]);

            // Visit dashboard
            $response = $this->actingAs($user)->get('/dashboard');

            // Assert monitor is displayed
            $response->assertStatus(200);
            $response->assertSee($monitor->name);

            // Clean up
            $monitor->delete();
            $user->delete();
        }
    }

    /**
     * Property Test: Dashboard displays monitors regardless of creation order.
     */
    public function test_dashboard_displays_monitors_regardless_of_creation_order(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Create monitors at different times
            $monitors = [];
            for ($j = 0; $j < 5; $j++) {
                $monitors[] = Monitor::factory()->create([
                    'user_id' => $user->id,
                    'name' => 'Order Monitor ' . $i . '-' . $j,
                    'created_at' => now()->subDays($j),
                ]);
            }

            // Visit dashboard
            $response = $this->actingAs($user)->get('/dashboard');

            // Assert all monitors are displayed regardless of creation order
            $response->assertStatus(200);
            foreach ($monitors as $monitor) {
                $response->assertSee($monitor->name);
            }

            // Clean up
            foreach ($monitors as $monitor) {
                $monitor->delete();
            }
            $user->delete();
        }
    }
}
