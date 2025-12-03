<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Check;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a user has many monitors relationship.
     *
     * @return void
     */
    public function test_user_has_many_monitors(): void
    {
        // Create a user with monitors
        $user = User::factory()->create();
        $monitors = Monitor::factory()->count(3)->create(['user_id' => $user->id]);

        // Refresh the user to load relationships
        $user->refresh();

        // Assert the relationship exists and returns correct count
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->monitors);
        $this->assertCount(3, $user->monitors);

        // Assert each monitor belongs to the user
        foreach ($user->monitors as $monitor) {
            $this->assertEquals($user->id, $monitor->user_id);
            $this->assertInstanceOf(Monitor::class, $monitor);
        }
    }

    /**
     * Test that a monitor belongs to a user relationship.
     *
     * @return void
     */
    public function test_monitor_belongs_to_user(): void
    {
        // Create a monitor with a user
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        // Refresh the monitor to load relationships
        $monitor->refresh();

        // Assert the relationship exists
        $this->assertInstanceOf(User::class, $monitor->user);
        $this->assertEquals($user->id, $monitor->user->id);
        $this->assertEquals($user->name, $monitor->user->name);
        $this->assertEquals($user->email, $monitor->user->email);
    }

    /**
     * Test that a monitor has many checks relationship.
     *
     * @return void
     */
    public function test_monitor_has_many_checks(): void
    {
        // Create a monitor with checks
        $monitor = Monitor::factory()->create();
        $checks = Check::factory()->count(5)->create(['monitor_id' => $monitor->id]);

        // Refresh the monitor to load relationships
        $monitor->refresh();

        // Assert the relationship exists and returns correct count
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $monitor->checks);
        $this->assertCount(5, $monitor->checks);

        // Assert each check belongs to the monitor
        foreach ($monitor->checks as $check) {
            $this->assertEquals($monitor->id, $check->monitor_id);
            $this->assertInstanceOf(Check::class, $check);
        }
    }

    /**
     * Test that a check belongs to a monitor relationship.
     *
     * @return void
     */
    public function test_check_belongs_to_monitor(): void
    {
        // Create a check with a monitor
        $monitor = Monitor::factory()->create();
        $check = Check::factory()->create(['monitor_id' => $monitor->id]);

        // Refresh the check to load relationships
        $check->refresh();

        // Assert the relationship exists
        $this->assertInstanceOf(Monitor::class, $check->monitor);
        $this->assertEquals($monitor->id, $check->monitor->id);
        $this->assertEquals($monitor->name, $check->monitor->name);
        $this->assertEquals($monitor->url, $check->monitor->url);
    }

    /**
     * Test that deleting a user cascades delete to monitors.
     *
     * @return void
     */
    public function test_user_deletion_cascades_to_monitors(): void
    {
        // Create a user with monitors
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        // Delete the user
        $user->delete();

        // Assert monitor is also deleted (cascade)
        $this->assertDatabaseMissing('monitors', ['id' => $monitor->id]);
    }

    /**
     * Test that deleting a monitor cascades delete to checks.
     *
     * @return void
     */
    public function test_monitor_deletion_cascades_to_checks(): void
    {
        // Create a monitor with checks
        $monitor = Monitor::factory()->create();
        $check = Check::factory()->create(['monitor_id' => $monitor->id]);

        // Delete the monitor
        $monitor->delete();

        // Assert check is also deleted (cascade)
        $this->assertDatabaseMissing('checks', ['id' => $check->id]);
    }

    /**
     * Test that a user can have zero monitors.
     *
     * @return void
     */
    public function test_user_can_have_zero_monitors(): void
    {
        // Create a user without monitors
        $user = User::factory()->create();

        // Assert the relationship returns an empty collection
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->monitors);
        $this->assertCount(0, $user->monitors);
        $this->assertTrue($user->monitors->isEmpty());
    }

    /**
     * Test that a monitor can have zero checks.
     *
     * @return void
     */
    public function test_monitor_can_have_zero_checks(): void
    {
        // Create a monitor without checks
        $monitor = Monitor::factory()->create();

        // Assert the relationship returns an empty collection
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $monitor->checks);
        $this->assertCount(0, $monitor->checks);
        $this->assertTrue($monitor->checks->isEmpty());
    }

    /**
     * Test that multiple users can have their own monitors.
     *
     * @return void
     */
    public function test_multiple_users_have_separate_monitors(): void
    {
        // Create two users with their own monitors
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $monitor1 = Monitor::factory()->create(['user_id' => $user1->id]);
        $monitor2 = Monitor::factory()->create(['user_id' => $user2->id]);

        // Refresh users
        $user1->refresh();
        $user2->refresh();

        // Assert each user has only their own monitor
        $this->assertCount(1, $user1->monitors);
        $this->assertCount(1, $user2->monitors);
        $this->assertEquals($monitor1->id, $user1->monitors->first()->id);
        $this->assertEquals($monitor2->id, $user2->monitors->first()->id);
    }
}
