<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Check;
use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelHelperMethodsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that isUp() returns true when monitor status is 'up'.
     *
     * @return void
     */
    public function test_is_up_returns_true_when_status_is_up(): void
    {
        $monitor = Monitor::factory()->up()->create();

        $this->assertTrue($monitor->isUp());
        $this->assertFalse($monitor->isDown());
        $this->assertFalse($monitor->isPending());
    }

    /**
     * Test that isUp() returns false when monitor status is not 'up'.
     *
     * @return void
     */
    public function test_is_up_returns_false_when_status_is_not_up(): void
    {
        $downMonitor = Monitor::factory()->down()->create();
        $pendingMonitor = Monitor::factory()->pending()->create();

        $this->assertFalse($downMonitor->isUp());
        $this->assertFalse($pendingMonitor->isUp());
    }

    /**
     * Test that isDown() returns true when monitor status is 'down'.
     *
     * @return void
     */
    public function test_is_down_returns_true_when_status_is_down(): void
    {
        $monitor = Monitor::factory()->down()->create();

        $this->assertTrue($monitor->isDown());
        $this->assertFalse($monitor->isUp());
        $this->assertFalse($monitor->isPending());
    }

    /**
     * Test that isDown() returns false when monitor status is not 'down'.
     *
     * @return void
     */
    public function test_is_down_returns_false_when_status_is_not_down(): void
    {
        $upMonitor = Monitor::factory()->up()->create();
        $pendingMonitor = Monitor::factory()->pending()->create();

        $this->assertFalse($upMonitor->isDown());
        $this->assertFalse($pendingMonitor->isDown());
    }

    /**
     * Test that isPending() returns true when monitor status is 'pending'.
     *
     * @return void
     */
    public function test_is_pending_returns_true_when_status_is_pending(): void
    {
        $monitor = Monitor::factory()->pending()->create();

        $this->assertTrue($monitor->isPending());
        $this->assertFalse($monitor->isUp());
        $this->assertFalse($monitor->isDown());
    }

    /**
     * Test that isPending() returns false when monitor status is not 'pending'.
     *
     * @return void
     */
    public function test_is_pending_returns_false_when_status_is_not_pending(): void
    {
        $upMonitor = Monitor::factory()->up()->create();
        $downMonitor = Monitor::factory()->down()->create();

        $this->assertFalse($upMonitor->isPending());
        $this->assertFalse($downMonitor->isPending());
    }

    /**
     * Test that wasSuccessful() returns true when check status is 'success'.
     *
     * @return void
     */
    public function test_was_successful_returns_true_when_status_is_success(): void
    {
        $check = Check::factory()->successful()->create();

        $this->assertTrue($check->wasSuccessful());
        $this->assertFalse($check->wasFailed());
    }

    /**
     * Test that wasSuccessful() returns false when check status is not 'success'.
     *
     * @return void
     */
    public function test_was_successful_returns_false_when_status_is_not_success(): void
    {
        $check = Check::factory()->failed()->create();

        $this->assertFalse($check->wasSuccessful());
    }

    /**
     * Test that wasFailed() returns true when check status is 'failed'.
     *
     * @return void
     */
    public function test_was_failed_returns_true_when_status_is_failed(): void
    {
        $check = Check::factory()->failed()->create();

        $this->assertTrue($check->wasFailed());
        $this->assertFalse($check->wasSuccessful());
    }

    /**
     * Test that wasFailed() returns false when check status is not 'failed'.
     *
     * @return void
     */
    public function test_was_failed_returns_false_when_status_is_not_failed(): void
    {
        $check = Check::factory()->successful()->create();

        $this->assertFalse($check->wasFailed());
    }

    /**
     * Test that monitor status methods work correctly after status change.
     *
     * @return void
     */
    public function test_monitor_status_methods_work_after_status_change(): void
    {
        $monitor = Monitor::factory()->pending()->create();

        // Initially pending
        $this->assertTrue($monitor->isPending());

        // Change to up
        $monitor->status = 'up';
        $monitor->save();
        $monitor->refresh();

        $this->assertTrue($monitor->isUp());
        $this->assertFalse($monitor->isPending());
        $this->assertFalse($monitor->isDown());

        // Change to down
        $monitor->status = 'down';
        $monitor->save();
        $monitor->refresh();

        $this->assertTrue($monitor->isDown());
        $this->assertFalse($monitor->isUp());
        $this->assertFalse($monitor->isPending());
    }

    /**
     * Test that check status methods work correctly after status change.
     *
     * @return void
     */
    public function test_check_status_methods_work_after_status_change(): void
    {
        $check = Check::factory()->successful()->create();

        // Initially successful
        $this->assertTrue($check->wasSuccessful());
        $this->assertFalse($check->wasFailed());

        // Change to failed
        $check->status = 'failed';
        $check->save();
        $check->refresh();

        $this->assertTrue($check->wasFailed());
        $this->assertFalse($check->wasSuccessful());
    }
}
