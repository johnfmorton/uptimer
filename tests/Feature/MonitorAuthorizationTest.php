<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that users can only view their own monitors.
     */
    public function test_users_can_only_view_their_own_monitors(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $monitor1 = Monitor::factory()->create(['user_id' => $user1->id]);
        $monitor2 = Monitor::factory()->create(['user_id' => $user2->id]);
        
        // User 1 can view their own monitor
        $response = $this->actingAs($user1)->get(route('monitors.show', $monitor1));
        $response->assertStatus(200);
        
        // User 1 cannot view user 2's monitor
        $response = $this->actingAs($user1)->get(route('monitors.show', $monitor2));
        $response->assertStatus(403);
    }

    /**
     * Test that users can only update their own monitors.
     */
    public function test_users_can_only_update_their_own_monitors(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $monitor1 = Monitor::factory()->create(['user_id' => $user1->id]);
        $monitor2 = Monitor::factory()->create(['user_id' => $user2->id]);
        
        // User 1 can access edit form for their own monitor
        $response = $this->actingAs($user1)->get(route('monitors.edit', $monitor1));
        $response->assertStatus(200);
        
        // User 1 cannot access edit form for user 2's monitor
        $response = $this->actingAs($user1)->get(route('monitors.edit', $monitor2));
        $response->assertStatus(403);
        
        // User 1 can update their own monitor
        $response = $this->actingAs($user1)->put(route('monitors.update', $monitor1), [
            'name' => 'Updated Name',
            'url' => 'https://example.com',
            'check_interval_minutes' => 10,
        ]);
        $response->assertRedirect(route('monitors.show', $monitor1));
        
        // User 1 cannot update user 2's monitor
        $response = $this->actingAs($user1)->put(route('monitors.update', $monitor2), [
            'name' => 'Updated Name',
            'url' => 'https://example.com',
            'check_interval_minutes' => 10,
        ]);
        $response->assertStatus(403);
    }

    /**
     * Test that users can only delete their own monitors.
     */
    public function test_users_can_only_delete_their_own_monitors(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $monitor1 = Monitor::factory()->create(['user_id' => $user1->id]);
        $monitor2 = Monitor::factory()->create(['user_id' => $user2->id]);
        
        // User 1 can delete their own monitor
        $response = $this->actingAs($user1)->delete(route('monitors.destroy', $monitor1));
        $response->assertRedirect(route('monitors.index'));
        $this->assertDatabaseMissing('monitors', ['id' => $monitor1->id]);
        
        // User 1 cannot delete user 2's monitor
        $response = $this->actingAs($user1)->delete(route('monitors.destroy', $monitor2));
        $response->assertStatus(403);
        $this->assertDatabaseHas('monitors', ['id' => $monitor2->id]);
    }

    /**
     * Test that unauthenticated users cannot access monitors.
     */
    public function test_unauthenticated_users_cannot_access_monitors(): void
    {
        $monitor = Monitor::factory()->create();
        
        // Unauthenticated users are redirected to login
        $response = $this->get(route('monitors.show', $monitor));
        $response->assertRedirect(route('login'));
        
        $response = $this->get(route('monitors.edit', $monitor));
        $response->assertRedirect(route('login'));
        
        $response = $this->put(route('monitors.update', $monitor), [
            'name' => 'Updated Name',
            'url' => 'https://example.com',
            'check_interval_minutes' => 10,
        ]);
        $response->assertRedirect(route('login'));
        
        $response = $this->delete(route('monitors.destroy', $monitor));
        $response->assertRedirect(route('login'));
    }
}
