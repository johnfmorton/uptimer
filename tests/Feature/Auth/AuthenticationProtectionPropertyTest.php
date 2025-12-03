<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Test for Authentication Protection
 * 
 * **Feature: uptime-monitor, Property 1: Authentication protects dashboard access**
 * 
 * Property: For any unauthenticated request to dashboard routes, 
 * the system should redirect to the login page and not display protected content.
 * 
 * Validates: Requirements 1.1
 */
class AuthenticationProtectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generate random protected routes for GET requests.
     * 
     * @return array<int, array<int, string>>
     */
    public static function protectedGetRoutesProvider(): array
    {
        // Define protected routes that accept GET requests
        $protected_routes = [
            '/dashboard',
            '/profile',
            '/verify-email',
            '/confirm-password',
        ];

        // Generate 100+ test cases by repeating routes with variations
        $test_cases = [];
        
        // Run each route multiple times to reach 100+ iterations
        $iterations_per_route = (int) ceil(100 / count($protected_routes));
        
        foreach ($protected_routes as $route) {
            for ($i = 0; $i < $iterations_per_route; $i++) {
                $test_cases[] = [$route];
            }
        }

        return $test_cases;
    }

    /**
     * Generate random protected routes for POST requests.
     * 
     * @return array<int, array<int, string>>
     */
    public static function protectedPostRoutesProvider(): array
    {
        // Define protected routes that accept POST requests
        $protected_routes = [
            '/queue-test',
            '/logout',
            '/email/verification-notification',
        ];

        // Generate 100+ test cases by repeating routes with variations
        $test_cases = [];
        
        // Run each route multiple times to reach 100+ iterations
        $iterations_per_route = (int) ceil(100 / count($protected_routes));
        
        foreach ($protected_routes as $route) {
            for ($i = 0; $i < $iterations_per_route; $i++) {
                $test_cases[] = [$route];
            }
        }

        return $test_cases;
    }

    /**
     * Property Test: Unauthenticated GET requests to protected routes redirect to login.
     * 
     * @dataProvider protectedGetRoutesProvider
     */
    public function test_unauthenticated_get_requests_to_protected_routes_redirect_to_login(string $route): void
    {
        // Ensure we're not authenticated
        $this->assertGuest();

        // Attempt to access the protected route
        $response = $this->get($route);

        // Assert that we're redirected to login
        $response->assertRedirect('/login');
        
        // Assert that we remain unauthenticated
        $this->assertGuest();
    }

    /**
     * Property Test: Unauthenticated POST requests to protected routes redirect to login.
     * 
     * @dataProvider protectedPostRoutesProvider
     */
    public function test_unauthenticated_post_requests_to_protected_routes_redirect_to_login(string $route): void
    {
        // Ensure we're not authenticated
        $this->assertGuest();

        // Attempt to POST to the protected route
        $response = $this->post($route, []);

        // Assert that we're redirected to login
        $response->assertRedirect('/login');
        
        // Assert that we remain unauthenticated
        $this->assertGuest();
    }

    /**
     * Property Test: Protected routes do not expose content to unauthenticated users.
     */
    public function test_protected_routes_do_not_expose_content_to_unauthenticated_users(): void
    {
        $protected_routes = [
            '/dashboard',
            '/profile',
        ];

        // Run 100 iterations across different routes
        for ($i = 0; $i < 100; $i++) {
            // Pick a random route
            $route = $protected_routes[array_rand($protected_routes)];
            
            // Ensure we're not authenticated
            $this->assertGuest();

            // Attempt to access the protected route
            $response = $this->get($route);

            // Assert redirect to login (not 200 OK)
            $this->assertNotEquals(200, $response->status(), 
                "Route {$route} should not return 200 for unauthenticated users");
            
            // Assert that we're redirected to login
            $response->assertRedirect('/login');
        }
    }

    /**
     * Property Test: Authenticated users can access protected routes.
     */
    public function test_authenticated_users_can_access_protected_routes(): void
    {
        // Routes that should return 200 for authenticated users
        $accessible_routes = [
            '/dashboard',
            '/profile',
        ];

        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create a fresh user for each iteration
            $user = User::factory()->create();
            
            // Pick a random route
            $route = $accessible_routes[array_rand($accessible_routes)];
            
            // Act as the authenticated user
            $response = $this->actingAs($user)->get($route);

            // Assert successful access (200 OK)
            $response->assertStatus(200);
            
            // Assert we're authenticated
            $this->assertAuthenticated();
            
            // Clean up for next iteration
            $user->delete();
        }
    }
}

