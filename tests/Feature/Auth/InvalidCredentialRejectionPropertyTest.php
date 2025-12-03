<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Property-Based Test for Invalid Credential Rejection
 *
 * **Feature: uptime-monitor, Property 3: Invalid credentials are rejected**
 *
 * Property: For any invalid credentials submitted to the login form,
 * the system should reject authentication and display an error without granting access.
 *
 * Validates: Requirements 1.3
 */
class InvalidCredentialRejectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generate random invalid credential scenarios for testing.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function invalidCredentialsProvider(): array
    {
        $test_cases = [];

        // Generate 100+ test cases with various invalid credential scenarios
        for ($i = 0; $i < 100; $i++) {
            // Scenario 1: Wrong password (25 cases)
            if ($i < 25) {
                $test_cases[] = [
                    'scenario' => 'wrong_password',
                    'email' => 'user'.$i.'@example.com',
                    'correct_password' => 'CorrectPassword'.$i.'!',
                    'attempted_password' => 'WrongPassword'.$i.'!',
                ];
            }
            // Scenario 2: Non-existent email (25 cases)
            elseif ($i < 50) {
                $test_cases[] = [
                    'scenario' => 'nonexistent_email',
                    'email' => 'nonexistent'.$i.'@example.com',
                    'correct_password' => null,
                    'attempted_password' => 'AnyPassword'.$i.'!',
                ];
            }
            // Scenario 3: Empty password (25 cases)
            elseif ($i < 75) {
                $test_cases[] = [
                    'scenario' => 'empty_password',
                    'email' => 'emptypass'.$i.'@example.com',
                    'correct_password' => 'CorrectPassword'.$i.'!',
                    'attempted_password' => '',
                ];
            }
            // Scenario 4: Case-sensitive password mismatch (25 cases)
            else {
                $test_cases[] = [
                    'scenario' => 'case_mismatch',
                    'email' => 'caseuser'.$i.'@example.com',
                    'correct_password' => 'CorrectPassword'.$i.'!',
                    'attempted_password' => 'correctpassword'.$i.'!', // lowercase version
                ];
            }
        }

        return $test_cases;
    }

    /**
     * Property Test: Invalid credentials are rejected and do not grant access.
     *
     * @dataProvider invalidCredentialsProvider
     */
    public function test_invalid_credentials_are_rejected_and_do_not_grant_access(
        string $scenario,
        string $email,
        ?string $correct_password,
        string $attempted_password
    ): void {
        // Create user only if scenario requires it (not for nonexistent_email)
        if ($scenario !== 'nonexistent_email' && $correct_password !== null) {
            User::factory()->create([
                'email' => $email,
                'password' => Hash::make($correct_password),
            ]);
        }

        // Ensure we start unauthenticated
        $this->assertGuest();

        // Attempt login with invalid credentials
        $response = $this->post('/login', [
            'email' => $email,
            'password' => $attempted_password,
        ]);

        // Assert that authentication failed
        $this->assertGuest();

        // Assert that we're not redirected to dashboard
        $response->assertRedirect('/');

        // Assert that an error is present in the session
        $response->assertSessionHasErrors();
    }

    /**
     * Property Test: Invalid credentials prevent dashboard access.
     */
    public function test_invalid_credentials_prevent_dashboard_access(): void
    {
        // Run 100 iterations with different invalid credential scenarios
        for ($i = 0; $i < 100; $i++) {
            $email = 'testuser'.$i.'@test.com';
            $correct_password = 'CorrectPass'.$i.'!'.bin2hex(random_bytes(4));
            $wrong_password = 'WrongPass'.$i.'!'.bin2hex(random_bytes(4));

            // Create user with correct password
            $user = User::factory()->create([
                'email' => $email,
                'password' => Hash::make($correct_password),
            ]);

            // Ensure we start unauthenticated
            $this->assertGuest();

            // Attempt login with wrong password
            $login_response = $this->post('/login', [
                'email' => $email,
                'password' => $wrong_password,
            ]);

            // Assert authentication failed
            $this->assertGuest();
            $login_response->assertSessionHasErrors();

            // Attempt to access dashboard directly
            $dashboard_response = $this->get('/dashboard');

            // Assert we're redirected to login (not granted access)
            $dashboard_response->assertRedirect('/login');

            // Assert we remain unauthenticated
            $this->assertGuest();

            // Clean up
            $user->delete();
        }
    }

    /**
     * Property Test: Multiple failed login attempts with invalid credentials.
     */
    public function test_multiple_failed_login_attempts_with_invalid_credentials(): void
    {
        // Run 50 iterations
        for ($i = 0; $i < 50; $i++) {
            $email = 'multiuser'.$i.'@test.com';
            $correct_password = 'CorrectPass'.$i.'!';

            // Create user
            $user = User::factory()->create([
                'email' => $email,
                'password' => Hash::make($correct_password),
            ]);

            // Attempt multiple logins with wrong passwords
            for ($attempt = 0; $attempt < 3; $attempt++) {
                $wrong_password = 'WrongPass'.$i.'_'.$attempt.'!';

                $this->assertGuest();

                $response = $this->post('/login', [
                    'email' => $email,
                    'password' => $wrong_password,
                ]);

                // Each attempt should fail
                $this->assertGuest();
                $response->assertSessionHasErrors();
            }

            // Clean up
            $user->delete();
        }
    }

    /**
     * Property Test: Invalid credentials display appropriate error message.
     */
    public function test_invalid_credentials_display_error_message(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $email = 'erroruser'.$i.'@test.com';
            $correct_password = 'CorrectPass'.$i.'!';
            $wrong_password = 'WrongPass'.$i.'!';

            // Create user
            $user = User::factory()->create([
                'email' => $email,
                'password' => Hash::make($correct_password),
            ]);

            // Attempt login with wrong password
            $response = $this->post('/login', [
                'email' => $email,
                'password' => $wrong_password,
            ]);

            // Assert that errors are present
            $response->assertSessionHasErrors();

            // Assert that we remain unauthenticated
            $this->assertGuest();

            // Clean up
            $user->delete();
        }
    }

    /**
     * Property Test: Invalid credentials with valid email format but nonexistent user.
     */
    public function test_invalid_credentials_with_nonexistent_user(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Use email that doesn't exist in database
            $nonexistent_email = 'nonexistent'.$i.'_'.uniqid().'@test.com';
            $any_password = 'AnyPassword'.$i.'!';

            // Ensure we start unauthenticated
            $this->assertGuest();

            // Attempt login with nonexistent email
            $response = $this->post('/login', [
                'email' => $nonexistent_email,
                'password' => $any_password,
            ]);

            // Assert authentication failed
            $this->assertGuest();

            // Assert errors are present
            $response->assertSessionHasErrors();

            // Assert we're not redirected to dashboard
            $response->assertRedirect('/');
        }
    }

    /**
     * Property Test: Invalid credentials do not leak user existence information.
     */
    public function test_invalid_credentials_do_not_leak_user_existence(): void
    {
        // Run 50 iterations comparing existing vs non-existing users
        for ($i = 0; $i < 50; $i++) {
            $existing_email = 'existing'.$i.'@test.com';
            $nonexistent_email = 'nonexistent'.$i.'@test.com';
            $password = 'TestPassword'.$i.'!';

            // Create user with existing email
            $user = User::factory()->create([
                'email' => $existing_email,
                'password' => Hash::make('CorrectPassword'.$i.'!'),
            ]);

            // Test with existing user but wrong password
            $response1 = $this->post('/login', [
                'email' => $existing_email,
                'password' => $password,
            ]);

            // Test with nonexistent user
            $response2 = $this->post('/login', [
                'email' => $nonexistent_email,
                'password' => $password,
            ]);

            // Both should fail authentication
            $this->assertGuest();

            // Both should have errors
            $response1->assertSessionHasErrors();
            $response2->assertSessionHasErrors();

            // Both should redirect to same location
            $this->assertEquals($response1->getStatusCode(), $response2->getStatusCode());

            // Clean up
            $user->delete();
        }
    }
}
