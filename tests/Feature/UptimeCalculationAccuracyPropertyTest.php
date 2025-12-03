<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Check;
use App\Models\Monitor;
use App\Models\User;
use App\Services\CheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Property-Based Test for Uptime Calculation Accuracy
 *
 * **Feature: uptime-monitor, Property 17: Uptime calculation accuracy**
 *
 * Property: For any monitor with check history, the uptime percentage should equal
 * (successful checks / total checks) × 100 for the specified time period.
 *
 * Validates: Requirements 11.4
 */
class UptimeCalculationAccuracyPropertyTest extends TestCase
{
    use RefreshDatabase;

    private CheckService $checkService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->checkService = new CheckService();
        $this->user = User::factory()->create();
    }

    /**
     * Generate random check scenarios for testing uptime calculation.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function uptimeCalculationProvider(): array
    {
        $test_cases = [];

        // Generate 100+ test cases with various check distributions
        for ($i = 0; $i < 120; $i++) {
            // Random number of total checks (1-100)
            $total_checks = rand(1, 100);
            
            // Random number of successful checks (0 to total_checks)
            $successful_checks = rand(0, $total_checks);
            
            // Calculate expected uptime percentage
            $expected_uptime = ($successful_checks / $total_checks) * 100;
            
            // Random time period in hours (1-720 hours = 1 hour to 30 days)
            $hours = [1, 6, 12, 24, 48, 72, 168, 720][$i % 8];

            $test_cases[] = [
                'total_checks' => $total_checks,
                'successful_checks' => $successful_checks,
                'expected_uptime' => $expected_uptime,
                'hours' => $hours,
            ];
        }

        return $test_cases;
    }

    /**
     * Property Test: Uptime calculation accuracy.
     *
     * @dataProvider uptimeCalculationProvider
     */
    public function test_uptime_calculation_equals_successful_divided_by_total_times_100(
        int $total_checks,
        int $successful_checks,
        float $expected_uptime,
        int $hours
    ): void {
        $monitor = Monitor::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $now = Carbon::now();
        $failed_checks = $total_checks - $successful_checks;

        // Create successful checks
        for ($i = 0; $i < $successful_checks; $i++) {
            // Distribute checks evenly within the time period
            $hours_ago = ($i * $hours) / $total_checks;
            
            Check::factory()->create([
                'monitor_id' => $monitor->id,
                'status' => 'success',
                'status_code' => 200,
                'response_time_ms' => rand(50, 500),
                'error_message' => null,
                'checked_at' => $now->copy()->subHours($hours_ago),
            ]);
        }

        // Create failed checks
        for ($i = 0; $i < $failed_checks; $i++) {
            // Distribute checks evenly within the time period
            $hours_ago = (($successful_checks + $i) * $hours) / $total_checks;
            
            Check::factory()->create([
                'monitor_id' => $monitor->id,
                'status' => 'failed',
                'status_code' => rand(400, 599),
                'response_time_ms' => null,
                'error_message' => 'HTTP error',
                'checked_at' => $now->copy()->subHours($hours_ago),
            ]);
        }

        // Calculate uptime
        $actual_uptime = $this->checkService->calculateUptime($monitor, $hours);

        // Assert uptime matches the formula: (successful / total) * 100
        $this->assertNotNull($actual_uptime);
        $this->assertEquals($expected_uptime, $actual_uptime, "Uptime calculation should equal (successful_checks / total_checks) * 100");
        
        // Verify the calculation manually
        $this->assertEquals(
            ($successful_checks / $total_checks) * 100,
            $actual_uptime,
            "Uptime should be calculated as (successful_checks / total_checks) * 100"
        );
    }

    /**
     * Property Test: 100% uptime when all checks are successful.
     */
    public function test_uptime_is_100_percent_when_all_checks_are_successful(): void
    {
        // Run 100 iterations with different numbers of checks
        for ($i = 1; $i <= 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
            ]);

            $now = Carbon::now();
            $num_checks = $i; // 1 to 100 checks

            // Create all successful checks
            for ($j = 0; $j < $num_checks; $j++) {
                Check::factory()->create([
                    'monitor_id' => $monitor->id,
                    'status' => 'success',
                    'status_code' => 200,
                    'checked_at' => $now->copy()->subHours($j),
                ]);
            }

            $uptime = $this->checkService->calculateUptime($monitor, 24);

            // Assert 100% uptime
            $this->assertEquals(100.0, $uptime, "Uptime should be 100% when all {$num_checks} checks are successful");
        }
    }

    /**
     * Property Test: 0% uptime when all checks are failed.
     */
    public function test_uptime_is_0_percent_when_all_checks_are_failed(): void
    {
        // Run 100 iterations with different numbers of checks
        for ($i = 1; $i <= 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
            ]);

            $now = Carbon::now();
            $num_checks = $i; // 1 to 100 checks

            // Create all failed checks
            for ($j = 0; $j < $num_checks; $j++) {
                Check::factory()->create([
                    'monitor_id' => $monitor->id,
                    'status' => 'failed',
                    'status_code' => 500,
                    'checked_at' => $now->copy()->subHours($j),
                ]);
            }

            $uptime = $this->checkService->calculateUptime($monitor, 24);

            // Assert 0% uptime
            $this->assertEquals(0.0, $uptime, "Uptime should be 0% when all {$num_checks} checks are failed");
        }
    }

    /**
     * Property Test: Uptime calculation only includes checks within time period.
     */
    public function test_uptime_calculation_only_includes_checks_within_time_period(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
            ]);

            $now = Carbon::now();
            $hours = [1, 6, 12, 24, 48, 72, 168][$i % 7];
            
            // Number of checks within period (should be counted)
            $checks_within = rand(5, 20);
            $successful_within = rand(0, $checks_within);
            $failed_within = $checks_within - $successful_within;
            
            // Number of checks outside period (should NOT be counted)
            $checks_outside = rand(5, 20);

            // Create successful checks within period
            for ($j = 0; $j < $successful_within; $j++) {
                $hours_ago = rand(0, $hours - 1);
                Check::factory()->create([
                    'monitor_id' => $monitor->id,
                    'status' => 'success',
                    'checked_at' => $now->copy()->subHours($hours_ago),
                ]);
            }

            // Create failed checks within period
            for ($j = 0; $j < $failed_within; $j++) {
                $hours_ago = rand(0, $hours - 1);
                Check::factory()->create([
                    'monitor_id' => $monitor->id,
                    'status' => 'failed',
                    'checked_at' => $now->copy()->subHours($hours_ago),
                ]);
            }

            // Create checks outside period (all failed to make it obvious if they're counted)
            for ($j = 0; $j < $checks_outside; $j++) {
                $hours_ago = $hours + rand(1, 100);
                Check::factory()->create([
                    'monitor_id' => $monitor->id,
                    'status' => 'failed',
                    'checked_at' => $now->copy()->subHours($hours_ago),
                ]);
            }

            $uptime = $this->checkService->calculateUptime($monitor, $hours);

            // Calculate expected uptime based only on checks within period
            $expected_uptime = ($successful_within / $checks_within) * 100;

            $this->assertNotNull($uptime);
            $this->assertEquals(
                $expected_uptime,
                $uptime,
                "Uptime should only include {$checks_within} checks within {$hours} hours, not the {$checks_outside} checks outside the period"
            );
        }
    }

    /**
     * Property Test: Uptime returns null when no checks exist in time period.
     */
    public function test_uptime_returns_null_when_no_checks_exist_in_time_period(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
            ]);

            $now = Carbon::now();
            $hours = [1, 6, 12, 24, 48, 72, 168, 720][$i % 8];
            
            // Create checks outside the time period
            $num_old_checks = rand(1, 20);
            for ($j = 0; $j < $num_old_checks; $j++) {
                $hours_ago = $hours + rand(1, 100);
                Check::factory()->create([
                    'monitor_id' => $monitor->id,
                    'status' => rand(0, 1) ? 'success' : 'failed',
                    'checked_at' => $now->copy()->subHours($hours_ago),
                ]);
            }

            $uptime = $this->checkService->calculateUptime($monitor, $hours);

            // Assert null when no checks in period
            $this->assertNull($uptime, "Uptime should be null when no checks exist within {$hours} hours");
        }
    }

    /**
     * Property Test: Uptime calculation with single check.
     */
    public function test_uptime_calculation_with_single_check(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
            ]);

            $now = Carbon::now();
            $is_successful = $i % 2 === 0; // Alternate between success and failure
            
            Check::factory()->create([
                'monitor_id' => $monitor->id,
                'status' => $is_successful ? 'success' : 'failed',
                'checked_at' => $now->copy()->subHours(1),
            ]);

            $uptime = $this->checkService->calculateUptime($monitor, 24);

            // With single check: 100% if successful, 0% if failed
            $expected_uptime = $is_successful ? 100.0 : 0.0;
            
            $this->assertNotNull($uptime);
            $this->assertEquals($expected_uptime, $uptime, "Single check should result in {$expected_uptime}% uptime");
        }
    }

    /**
     * Property Test: Uptime calculation with various success ratios.
     */
    public function test_uptime_calculation_with_various_success_ratios(): void
    {
        $test_ratios = [
            ['successful' => 1, 'total' => 10, 'expected' => 10.0],
            ['successful' => 2, 'total' => 10, 'expected' => 20.0],
            ['successful' => 3, 'total' => 10, 'expected' => 30.0],
            ['successful' => 4, 'total' => 10, 'expected' => 40.0],
            ['successful' => 5, 'total' => 10, 'expected' => 50.0],
            ['successful' => 6, 'total' => 10, 'expected' => 60.0],
            ['successful' => 7, 'total' => 10, 'expected' => 70.0],
            ['successful' => 8, 'total' => 10, 'expected' => 80.0],
            ['successful' => 9, 'total' => 10, 'expected' => 90.0],
            ['successful' => 10, 'total' => 10, 'expected' => 100.0],
        ];

        // Run each ratio 10 times (100 total iterations)
        foreach ($test_ratios as $ratio) {
            for ($i = 0; $i < 10; $i++) {
                $monitor = Monitor::factory()->create([
                    'user_id' => $this->user->id,
                ]);

                $now = Carbon::now();
                
                // Create successful checks
                for ($j = 0; $j < $ratio['successful']; $j++) {
                    Check::factory()->create([
                        'monitor_id' => $monitor->id,
                        'status' => 'success',
                        'checked_at' => $now->copy()->subHours($j),
                    ]);
                }

                // Create failed checks
                $failed = $ratio['total'] - $ratio['successful'];
                for ($j = 0; $j < $failed; $j++) {
                    Check::factory()->create([
                        'monitor_id' => $monitor->id,
                        'status' => 'failed',
                        'checked_at' => $now->copy()->subHours($ratio['successful'] + $j),
                    ]);
                }

                $uptime = $this->checkService->calculateUptime($monitor, 24);

                $this->assertNotNull($uptime);
                $this->assertEquals(
                    $ratio['expected'],
                    $uptime,
                    "Uptime should be {$ratio['expected']}% with {$ratio['successful']} successful out of {$ratio['total']} total checks"
                );
            }
        }
    }

    /**
     * Property Test: Uptime calculation is consistent across different time periods.
     */
    public function test_uptime_calculation_is_consistent_across_different_time_periods(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
            ]);

            $now = Carbon::now();
            
            // Create checks within last 24 hours
            $total_checks = rand(10, 50);
            $successful_checks = rand(0, $total_checks);
            $failed_checks = $total_checks - $successful_checks;
            
            for ($j = 0; $j < $successful_checks; $j++) {
                Check::factory()->create([
                    'monitor_id' => $monitor->id,
                    'status' => 'success',
                    'checked_at' => $now->copy()->subHours(rand(0, 23)),
                ]);
            }
            
            for ($j = 0; $j < $failed_checks; $j++) {
                Check::factory()->create([
                    'monitor_id' => $monitor->id,
                    'status' => 'failed',
                    'checked_at' => $now->copy()->subHours(rand(0, 23)),
                ]);
            }

            // Calculate uptime for 24 hours
            $uptime_24h = $this->checkService->calculateUptime($monitor, 24);
            
            // Calculate uptime for 48 hours (should be same since all checks are within 24h)
            $uptime_48h = $this->checkService->calculateUptime($monitor, 48);
            
            // Calculate uptime for 168 hours (should be same since all checks are within 24h)
            $uptime_168h = $this->checkService->calculateUptime($monitor, 168);

            // All should be equal since all checks are within 24 hours
            $this->assertEquals($uptime_24h, $uptime_48h, "Uptime should be consistent across different time periods when all checks are within the shorter period");
            $this->assertEquals($uptime_24h, $uptime_168h, "Uptime should be consistent across different time periods when all checks are within the shorter period");
            
            // Verify the calculation
            $expected_uptime = ($successful_checks / $total_checks) * 100;
            $this->assertEquals($expected_uptime, $uptime_24h);
        }
    }

    /**
     * Property Test: Uptime calculation with checks at time period boundary.
     */
    public function test_uptime_calculation_with_checks_at_time_period_boundary(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
            ]);

            $now = Carbon::now();
            $hours = 24;
            
            // Create check exactly at the boundary (should be included)
            Check::factory()->create([
                'monitor_id' => $monitor->id,
                'status' => 'success',
                'checked_at' => $now->copy()->subHours($hours),
            ]);
            
            // Create check just inside the boundary (should be included)
            Check::factory()->create([
                'monitor_id' => $monitor->id,
                'status' => 'success',
                'checked_at' => $now->copy()->subHours($hours - 1),
            ]);
            
            // Create check just outside the boundary (should NOT be included)
            Check::factory()->create([
                'monitor_id' => $monitor->id,
                'status' => 'failed',
                'checked_at' => $now->copy()->subHours($hours + 1),
            ]);

            $uptime = $this->checkService->calculateUptime($monitor, $hours);

            // Should include 2 successful checks (at boundary and inside)
            // Should NOT include the failed check outside boundary
            $this->assertNotNull($uptime);
            $this->assertEquals(100.0, $uptime, "Uptime should be 100% - checks at/inside boundary should be included, outside should not");
        }
    }

    /**
     * Property Test: Uptime calculation with large number of checks.
     */
    public function test_uptime_calculation_with_large_number_of_checks(): void
    {
        // Run 10 iterations with large datasets
        for ($i = 0; $i < 10; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
            ]);

            $now = Carbon::now();
            $total_checks = rand(500, 1000);
            $successful_checks = rand(0, $total_checks);
            $failed_checks = $total_checks - $successful_checks;
            
            // Create successful checks
            for ($j = 0; $j < $successful_checks; $j++) {
                Check::factory()->create([
                    'monitor_id' => $monitor->id,
                    'status' => 'success',
                    'checked_at' => $now->copy()->subMinutes($j),
                ]);
            }
            
            // Create failed checks
            for ($j = 0; $j < $failed_checks; $j++) {
                Check::factory()->create([
                    'monitor_id' => $monitor->id,
                    'status' => 'failed',
                    'checked_at' => $now->copy()->subMinutes($successful_checks + $j),
                ]);
            }

            $uptime = $this->checkService->calculateUptime($monitor, 24);

            // Calculate expected uptime
            $expected_uptime = ($successful_checks / $total_checks) * 100;
            
            $this->assertNotNull($uptime);
            $this->assertEquals(
                $expected_uptime,
                $uptime,
                "Uptime calculation should be accurate even with {$total_checks} checks"
            );
        }
    }
}
