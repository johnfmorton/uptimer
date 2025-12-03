<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Check;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneOldChecksCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that old checks are deleted based on retention period.
     */
    public function test_prunes_old_checks(): void
    {
        config(['monitoring.check_retention_days' => 30]);

        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        // Create old checks (35 days ago - should be deleted)
        $old_checks = Check::factory()->count(5)->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subDays(35),
        ]);

        // Create recent checks (25 days ago - should be kept)
        $recent_checks = Check::factory()->count(3)->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subDays(25),
        ]);

        $this->assertDatabaseCount('checks', 8);

        $this->artisan('checks:prune')
            ->assertExitCode(0);

        $this->assertDatabaseCount('checks', 3);

        // Verify old checks are gone
        foreach ($old_checks as $check) {
            $this->assertDatabaseMissing('checks', ['id' => $check->id]);
        }

        // Verify recent checks remain
        foreach ($recent_checks as $check) {
            $this->assertDatabaseHas('checks', ['id' => $check->id]);
        }
    }

    /**
     * Test that no checks are deleted when none are old enough.
     */
    public function test_no_checks_deleted_when_none_old_enough(): void
    {
        config(['monitoring.check_retention_days' => 30]);

        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        // Create recent checks (10 days ago)
        Check::factory()->count(5)->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subDays(10),
        ]);

        $this->assertDatabaseCount('checks', 5);

        $this->artisan('checks:prune')
            ->assertExitCode(0);

        $this->assertDatabaseCount('checks', 5);
    }

    /**
     * Test command with custom days option.
     */
    public function test_command_with_custom_days_option(): void
    {
        config(['monitoring.check_retention_days' => 30]);

        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        // Create checks at different ages
        Check::factory()->count(3)->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subDays(10),
        ]);

        Check::factory()->count(2)->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subDays(8),
        ]);

        $this->assertDatabaseCount('checks', 5);

        // Use --days option to delete checks older than 9 days (overrides config)
        $this->artisan('checks:prune', ['--days' => 9])
            ->assertExitCode(0);

        // Only the 10-day-old checks should be deleted
        $this->assertDatabaseCount('checks', 2);
    }

    /**
     * Test dry run mode does not delete checks.
     */
    public function test_dry_run_does_not_delete_checks(): void
    {
        config(['monitoring.check_retention_days' => 30]);

        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        // Create old checks (35 days ago)
        Check::factory()->count(5)->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subDays(35),
        ]);

        $this->assertDatabaseCount('checks', 5);

        $this->artisan('checks:prune --dry-run')
            ->assertExitCode(0);

        // Verify nothing was deleted
        $this->assertDatabaseCount('checks', 5);
    }

    /**
     * Test that retention period of 0 keeps all checks.
     */
    public function test_retention_period_zero_keeps_all_checks(): void
    {
        config(['monitoring.check_retention_days' => 0]);

        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        // Create very old checks (365 days ago)
        Check::factory()->count(5)->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subDays(365),
        ]);

        $this->assertDatabaseCount('checks', 5);

        $this->artisan('checks:prune')
            ->assertExitCode(0);

        // All checks should remain
        $this->assertDatabaseCount('checks', 5);
    }

    /**
     * Test custom retention period.
     */
    public function test_custom_retention_period(): void
    {
        config(['monitoring.check_retention_days' => 7]);

        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        // Create checks at different ages
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subDays(10), // Should be deleted
        ]);

        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'checked_at' => now()->subDays(5), // Should be kept
        ]);

        $this->assertDatabaseCount('checks', 2);

        $this->artisan('checks:prune')
            ->assertExitCode(0);

        $this->assertDatabaseCount('checks', 1);
    }
}
