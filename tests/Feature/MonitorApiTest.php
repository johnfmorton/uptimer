<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the API endpoint returns monitors for authenticated user.
     */
    public function test_api_endpoint_returns_user_monitors(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/monitors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'monitors' => [
                    '*' => [
                        'id',
                        'name',
                        'url',
                        'status',
                        'last_checked_at',
                        'last_checked_at_human',
                        'latest_response_time_ms',
                    ],
                ],
            ])
            ->assertJsonPath('monitors.0.id', $monitor->id)
            ->assertJsonPath('monitors.0.name', $monitor->name)
            ->assertJsonPath('monitors.0.url', $monitor->url)
            ->assertJsonPath('monitors.0.status', $monitor->status);
    }

    /**
     * Test that the API endpoint requires authentication.
     */
    public function test_api_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/monitors');

        $response->assertStatus(401);
    }

    /**
     * Test that the API endpoint only returns the authenticated user's monitors.
     */
    public function test_api_endpoint_only_returns_user_own_monitors(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $monitor1 = Monitor::factory()->create(['user_id' => $user1->id]);
        $monitor2 = Monitor::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson('/api/monitors');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'monitors')
            ->assertJsonPath('monitors.0.id', $monitor1->id);
    }

    /**
     * Test that the API endpoint returns optimized fields only.
     */
    public function test_api_endpoint_returns_optimized_fields(): void
    {
        $user = User::factory()->create();
        Monitor::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/monitors');

        $response->assertStatus(200);
        
        $monitor = $response->json('monitors.0');
        
        // Verify expected fields are present
        $this->assertArrayHasKey('id', $monitor);
        $this->assertArrayHasKey('name', $monitor);
        $this->assertArrayHasKey('url', $monitor);
        $this->assertArrayHasKey('status', $monitor);
        $this->assertArrayHasKey('last_checked_at', $monitor);
        $this->assertArrayHasKey('last_checked_at_human', $monitor);
        $this->assertArrayHasKey('latest_response_time_ms', $monitor);
        
        // Verify unnecessary fields are not present
        $this->assertArrayNotHasKey('created_at', $monitor);
        $this->assertArrayNotHasKey('updated_at', $monitor);
        $this->assertArrayNotHasKey('user_id', $monitor);
    }
}
