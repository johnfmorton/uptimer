<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Property-Based Test for Valid Credential Authentication
 *
 * **Feature: uptime-monitor, Property 2: Valid credentials grant access**
 *
 * Property: For any valid administrator credentials submitted to the login form,
 * the system should authenticate the user and allow access to the dashboard.
 *
 * Validates: Requirements 1.2
 */
class ValidCredentialAuthenticationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generate random valid user credentials for testing.
     *
     * @return array<int, array<string, string>>
     */
    public static function validCredentialsProvider(): array
    {
        $test_cases = [];

        // Generate 100+ test cases with random valid credentials
        for ($i = 0; $i < 100; $i++) {
            // Generate random email addresses
            $email = 'user'.$i.'_'.uniqid().'@example.com';

            // Generate random passwords (meeting minimum requirements)
            $password = 'Password'.$i.'!'.bin2hex(random_bytes(4));

            $test_cases[] = [
                'email' => $email,
                'password' => $password,
            ];
        }

        return $test_cases;
    }

    /**
     * Property Test: Valid credentials authenticate user and grant dashboard access.
     *
     * @dataProvider validCredentialsProvider
     */
    public function test_valid_credentials_authenticate_user_and_grant_dashboard_access(
        string $email,
        string $password
    ): void {
        // Create a user with the provided credentials
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // Ensure we start unauthenticated
        $this->assertGuest();

        // Submit valid credentials to login form
        $response = $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);

        // Assert that the user is now authenticated
        $this->assertAuthenticated();

        // Assert that we're redirected to the dashboard
        $response->assertRedirect(route('dashboard', absolute: false));

        // Verify the authenticated user is the correct one
        $this->assertEquals($user->id, auth()->id());
    }

    /**
     * Property Test: Valid credentials allow access to dashboard after login.
     */
    public function test_valid_credentials_allow_dashboard_access_after_login(): void
    {
        // Run 100 iterations with different users
        for ($i = 0; $i < 100; $i++) {
            // Generate random credentials
            $email = 'testuser'.$i.'_'.uniqid().'@test.com';
            $password = 'SecurePass'.$i.'!'.bin2hex(random_bytes(4));

            // Create user with these credentials
            $user = User::factory()->create([
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            // Ensure we start unauthenticated
            $this->assertGuest();

            // Attempt login with valid credentials
            $login_response = $this->post('/login', [
                'email' => $email,
                'password' => $password,
            ]);

            // Assert authentication succeeded
            $this->assertAuthenticated();
            $login_response->assertRedirect(route('dashboard', absolute: false));

            // Now verify we can actually access the dashboard
            $dashboard_response = $this->get('/dashboard');

            // Assert successful access to dashboard
            $dashboard_response->assertStatus(200);

            // Assert we're still authenticated
            $this->assertAuthenticated();

            // Logout for next iteration
            $this->post('/logout');
            $this->assertGuest();

            // Clean up
            $user->delete();
        }
    }

    /**
     * Property Test: Valid credentials work with various password complexities.
     */
    public function test_valid_credentials_work_with_various_password_complexities(): void
    {
        // Run 50 iterations with different password patterns
        for ($i = 0; $i < 50; $i++) {
            $email = 'complexuser'.$i.'@test.com';

            // Generate passwords with different complexities
            $passwords = [
                'Simple123!',
                'VeryLongPasswordWithManyCharacters123!@#',
                'P@ssw0rd'.$i,
                'Test_Pass_'.bin2hex(random_bytes(8)),
                'MixedCase123!@#$%^&*()',
            ];

            $password = $passwords[$i % count($passwords)].$i;

            // Create user with this password
            $user = User::factory()->create([
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            // Test login with exact password
            $this->assertGuest();
            $response = $this->post('/login', [
                'email' => $email,
                'password' => $password,
            ]);
            $this->assertAuthenticated();
            $response->assertRedirect(route('dashboard', absolute: false));
            $this->post('/logout');

            // Clean up
            $user->delete();
        }
    }

    /**
     * Property Test: Valid credentials maintain session across requests.
     */
    public function test_valid_credentials_maintain_session_across_requests(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $email = 'sessionuser'.$i.'@test.com';
            $password = 'SessionPass'.$i.'!'.bin2hex(random_bytes(4));

            $user = User::factory()->create([
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            // Login with valid credentials
            $this->post('/login', [
                'email' => $email,
                'password' => $password,
            ]);

            // Assert authenticated
            $this->assertAuthenticated();

            // Make multiple requests to verify session persists
            $this->get('/dashboard')->assertStatus(200);
            $this->assertAuthenticated();

            $this->get('/profile')->assertStatus(200);
            $this->assertAuthenticated();

            // Logout and clean up
            $this->post('/logout');
            $this->assertGuest();
            $user->delete();
        }
    }
}
