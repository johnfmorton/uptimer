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
 * Property-Based Test for Manual Check Dispatch
 *
 * **Feature: queue-diagnostics, Property 10: Manual check dispatch**
 *
 * Property: For any monitor and user action triggering "Check Now",
 * the Application should dispatch exactly one PerformMonitorCheck job
 * for that monitor to the Queue System.
 *
 * Validates: Requirements 3.1
 */
class ManualCheckDispatchPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generate random monitor data for testing.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function randomMonitorDataProvider(): array
    {
        $test_cases = [];

        // Generate 100+ test cases with random monitor configurations
        for ($i = 0; $i < 100; $i++) {
            // Generate various valid URL formats
            $url_patterns = [
                'https://example'.$i.'.com',
                'http://test-site-'.$i.'.org',
                'https://subdomain.example'.$i.'.net',
                'https://example.com/path'.$i,
                'https://example.com:8080/path'.$i,
                'https://api.example'.$i.'.com/v1/endpoint',
            ];

            $url = $url_patterns[$i % count($url_patterns)];

            // Generate random monitor names
            $name_patterns = [
                'Monitor '.$i,
                'Test Site '.$i,
                'Production Server '.$i,
                'API Endpoint '.$i,
                'Website Monitor '.$i,
            ];

            $name = $name_patterns[$i % count($name_patterns)];

            // Generate random check intervals (1-1440 minutes)
            $check_interval = rand(1, 1440);

            // Generate random status
            $statuses = ['pending', 'up', 'down'];
            $status = $statuses[$i % count($statuses)];

            $test_cases[] = [
                'name' => $name,
                'url' => $url,
                'check_interval_minutes' => $check_interval,
                'status' => $status,
            ];
        }

        return $test_cases;
    }

    /**
     * Property Test: Manual check dispatch queues exactly one job.
     *
     * @dataProvider randomMonitorDataProvider
     */
    public function test_manual_check_dispatch_queues_exactly_one_job(
        string $name,
        string $url,
        int $check_interval_minutes,
        string $status
    ): void {
        // Fake the queue to capture dispatched jobs
        Queue::fake();

        // Create an authenticated user
        $user = User::factory()->create();

        // Create a monitor owned by the user
        $monitor = Monitor::create([
            'user_id' => $user->id,
            'name' => $name,
            'url' => $url,
            'check_interval_minutes' => $check_interval_minutes,
            'status' => $status,
        ]);

        // Act as the authenticated user
        $this->actingAs($user);

        // Trigger manual check via POST request
        $response = $this->post(route('monitors.check', $monitor));

        // Assert the response is a redirect
        $response->assertRedirect();

        // Assert exactly one PerformMonitorCheck job was dispatched
        Queue::assertPushed(PerformMonitorCheck::class, 1);

        // Assert the job was dispatched for the correct monitor
        Queue::assertPushed(PerformMonitorCheck::class, function ($job) use ($monitor) {
            return $job->monitor->id === $monitor->id;
        });

        // Clean up
        $monitor->delete();
        $user->delete();
    }

    /**
     * Property Test: Manual check dispatch works for monitors with different statuses.
     */
    public function test_manual_check_dispatch_works_for_all_monitor_statuses(): void
    {
        Queue::fake();

        $statuses = ['pending', 'up', 'down'];

        // Run 100 iterations with different monitor statuses
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();
            $status = $statuses[$i % count($statuses)];

            $monitor = Monitor::create([
                'user_id' => $user->id,
                'name' => 'Status Test '.$i,
                'url' => 'https://example'.$i.'.com',
                'check_interval_minutes' => 5,
                'status' => $status,
            ]);

            $this->actingAs($user);

            // Trigger manual check
            $response = $this->post(route('monitors.check', $monitor));

            // Assert response is successful
            $response->assertRedirect();

            // Assert job was dispatched
            Queue::assertPushed(PerformMonitorCheck::class, function ($job) use ($monitor) {
                return $job->monitor->id === $monitor->id;
            });

            // Clean up
            $monitor->delete();
            $user->delete();

            // Reset queue fake for next iteration
            Queue::fake();
        }
    }

    /**
     * Property Test: Manual check dispatch works for monitors with different check intervals.
     */
    public function test_manual_check_dispatch_works_for_all_check_intervals(): void
    {
        Queue::fake();

        $check_intervals = [1, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440];

        // Run 100 iterations with different check intervals
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();
            $check_interval = $check_intervals[$i % count($check_intervals)];

            $monitor = Monitor::create([
                'user_id' => $user->id,
                'name' => 'Interval Test '.$i,
                'url' => 'https://example'.$i.'.com',
                'check_interval_minutes' => $check_interval,
                'status' => 'pending',
            ]);

            $this->actingAs($user);

            // Trigger manual check
            $response = $this->post(route('monitors.check', $monitor));

            // Assert response is successful
            $response->assertRedirect();

            // Assert job was dispatched
            Queue::assertPushed(PerformMonitorCheck::class, function ($job) use ($monitor) {
                return $job->monitor->id === $monitor->id;
            });

            // Clean up
            $monitor->delete();
            $user->delete();

            // Reset queue fake for next iteration
            Queue::fake();
        }
    }

    /**
     * Property Test: Manual check dispatch requires authentication.
     */
    public function test_manual_check_dispatch_requires_authentication(): void
    {
        Queue::fake();

        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            $monitor = Monitor::create([
                'user_id' => $user->id,
                'name' => 'Auth Test '.$i,
                'url' => 'https://example'.$i.'.com',
                'check_interval_minutes' => 5,
                'status' => 'pending',
            ]);

            // Attempt to trigger check without authentication
            $response = $this->post(route('monitors.check', $monitor));

            // Assert redirect to login
            $response->assertRedirect(route('login'));

            // Assert no job was dispatched
            Queue::assertNotPushed(PerformMonitorCheck::class);

            // Clean up
            $monitor->delete();
            $user->delete();

            // Reset queue fake for next iteration
            Queue::fake();
        }
    }

    /**
     * Property Test: Manual check dispatch requires monitor ownership.
     */
    public function test_manual_check_dispatch_requires_monitor_ownership(): void
    {
        Queue::fake();

        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $owner = User::factory()->create();
            $other_user = User::factory()->create();

            $monitor = Monitor::create([
                'user_id' => $owner->id,
                'name' => 'Ownership Test '.$i,
                'url' => 'https://example'.$i.'.com',
                'check_interval_minutes' => 5,
                'status' => 'pending',
            ]);

            // Act as a different user (not the owner)
            $this->actingAs($other_user);

            // Attempt to trigger check
            $response = $this->post(route('monitors.check', $monitor));

            // Assert forbidden response
            $response->assertForbidden();

            // Assert no job was dispatched
            Queue::assertNotPushed(PerformMonitorCheck::class);

            // Clean up
            $monitor->delete();
            $owner->delete();
            $other_user->delete();

            // Reset queue fake for next iteration
            Queue::fake();
        }
    }

    /**
     * Property Test: Manual check dispatch only dispatches one job per request.
     */
    public function test_manual_check_dispatch_only_dispatches_one_job_per_request(): void
    {
        Queue::fake();

        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            $monitor = Monitor::create([
                'user_id' => $user->id,
                'name' => 'Single Job Test '.$i,
                'url' => 'https://example'.$i.'.com',
                'check_interval_minutes' => 5,
                'status' => 'pending',
            ]);

            $this->actingAs($user);

            // Trigger manual check
            $this->post(route('monitors.check', $monitor));

            // Assert exactly one job was dispatched (not zero, not multiple)
            Queue::assertPushed(PerformMonitorCheck::class, 1);

            // Clean up
            $monitor->delete();
            $user->delete();

            // Reset queue fake for next iteration
            Queue::fake();
        }
    }

    /**
     * Property Test: Multiple manual checks for different monitors dispatch separate jobs.
     */
    public function test_multiple_manual_checks_for_different_monitors_dispatch_separate_jobs(): void
    {
        Queue::fake();

        // Run 50 iterations (creating 2 monitors each time = 100 total monitors)
        for ($i = 0; $i < 50; $i++) {
            $user = User::factory()->create();

            $monitor1 = Monitor::create([
                'user_id' => $user->id,
                'name' => 'Monitor A '.$i,
                'url' => 'https://example-a-'.$i.'.com',
                'check_interval_minutes' => 5,
                'status' => 'pending',
            ]);

            $monitor2 = Monitor::create([
                'user_id' => $user->id,
                'name' => 'Monitor B '.$i,
                'url' => 'https://example-b-'.$i.'.com',
                'check_interval_minutes' => 10,
                'status' => 'up',
            ]);

            $this->actingAs($user);

            // Trigger checks for both monitors
            $this->post(route('monitors.check', $monitor1));
            $this->post(route('monitors.check', $monitor2));

            // Assert exactly two jobs were dispatched
            Queue::assertPushed(PerformMonitorCheck::class, 2);

            // Assert each job is for the correct monitor
            Queue::assertPushed(PerformMonitorCheck::class, function ($job) use ($monitor1) {
                return $job->monitor->id === $monitor1->id;
            });

            Queue::assertPushed(PerformMonitorCheck::class, function ($job) use ($monitor2) {
                return $job->monitor->id === $monitor2->id;
            });

            // Clean up
            $monitor1->delete();
            $monitor2->delete();
            $user->delete();

            // Reset queue fake for next iteration
            Queue::fake();
        }
    }
}
