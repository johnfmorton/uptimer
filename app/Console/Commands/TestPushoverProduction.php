<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NotificationSettings;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestPushoverProduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pushover:test-production 
                            {--user-id= : Specific user ID to test with}
                            {--create-settings : Create test settings if none exist}
                            {--direct : Test Pushover API directly without Laravel service}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Pushover notifications in production environment with detailed logging';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== PRODUCTION PUSHOVER TEST ===');
        $this->info('Server: ' . gethostname());
        $this->info('Environment: ' . config('app.env'));
        $this->info('Time: ' . now()->format('Y-m-d H:i:s T'));
        $this->newLine();

        // Log the test initiation
        Log::channel('notifications')->info('Production Pushover test initiated via artisan command', [
            'server' => gethostname(),
            'environment' => config('app.env'),
            'user_id' => $this->option('user-id'),
            'create_settings' => $this->option('create-settings'),
            'direct_test' => $this->option('direct'),
        ]);

        // 1. Check environment variables
        $this->checkEnvironmentVariables();

        // 2. Test network connectivity
        $this->testNetworkConnectivity();

        // 3. Find or create user with Pushover settings
        $user = $this->findOrCreateTestUser();
        if (!$user) {
            $this->error('Could not find or create a user for testing');
            return 1;
        }

        // 4. Test direct API if requested
        if ($this->option('direct')) {
            $this->testDirectPushoverAPI();
        }

        // 5. Test Laravel notification service
        $this->testLaravelNotificationService($user);

        $this->newLine();
        $this->info('=== TEST COMPLETE ===');
        $this->info('Check notification logs: php artisan notifications:logs');
        
        return 0;
    }

    /**
     * Check environment variables configuration.
     */
    private function checkEnvironmentVariables(): void
    {
        $this->info('1. CHECKING ENVIRONMENT VARIABLES:');
        
        $userKey = env('PUSHOVER_USER_KEY');
        $apiToken = env('PUSHOVER_API_TOKEN');
        
        $this->line('   PUSHOVER_USER_KEY: ' . ($userKey ? 'SET (' . strlen($userKey) . ' chars)' : 'NOT SET'));
        $this->line('   PUSHOVER_API_TOKEN: ' . ($apiToken ? 'SET (' . strlen($apiToken) . ' chars)' : 'NOT SET'));
        
        if (!$userKey || !$apiToken) {
            $this->warn('   ⚠️  Pushover credentials not configured in environment');
            Log::channel('notifications')->warning('Pushover credentials missing from environment', [
                'user_key_set' => !empty($userKey),
                'api_token_set' => !empty($apiToken),
            ]);
        } else {
            $this->line('   ✅ Pushover credentials configured');
            Log::channel('notifications')->info('Pushover credentials found in environment', [
                'user_key_length' => strlen($userKey),
                'api_token_length' => strlen($apiToken),
            ]);
        }
        
        $this->newLine();
    }

    /**
     * Test network connectivity to Pushover API.
     */
    private function testNetworkConnectivity(): void
    {
        $this->info('2. TESTING NETWORK CONNECTIVITY:');
        
        // DNS Resolution
        $this->line('   Testing DNS resolution for api.pushover.net...');
        $dnsResult = gethostbyname('api.pushover.net');
        if ($dnsResult !== 'api.pushover.net') {
            $this->line("   ✅ DNS Resolution: SUCCESS ($dnsResult)");
            Log::channel('notifications')->info('DNS resolution successful', ['ip' => $dnsResult]);
        } else {
            $this->line('   ❌ DNS Resolution: FAILED');
            Log::channel('notifications')->error('DNS resolution failed for api.pushover.net');
        }

        // HTTPS Connectivity
        $this->line('   Testing HTTPS connectivity...');
        try {
            $response = Http::timeout(10)->get('https://api.pushover.net');
            if ($response->successful()) {
                $this->line('   ✅ HTTPS Connectivity: SUCCESS');
                Log::channel('notifications')->info('HTTPS connectivity test successful', [
                    'status_code' => $response->status(),
                ]);
            } else {
                $this->line('   ❌ HTTPS Connectivity: FAILED (HTTP ' . $response->status() . ')');
                Log::channel('notifications')->error('HTTPS connectivity test failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            $this->line('   ❌ HTTPS Connectivity: FAILED (' . $e->getMessage() . ')');
            Log::channel('notifications')->error('HTTPS connectivity test exception', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);
        }
        
        $this->newLine();
    }

    /**
     * Find or create a user with Pushover settings for testing.
     */
    private function findOrCreateTestUser(): ?User
    {
        $this->info('3. FINDING TEST USER:');
        
        $userId = $this->option('user-id');
        
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("   User with ID $userId not found");
                return null;
            }
            $this->line("   Using specified user: {$user->id}");
        } else {
            // Find user with Pushover enabled
            $user = User::whereHas('notificationSettings', function($query) {
                $query->where('pushover_enabled', true)
                      ->whereNotNull('pushover_user_key')
                      ->whereNotNull('pushover_api_token');
            })->first();
            
            if ($user) {
                $this->line("   Found user with Pushover configured: {$user->id}");
            } else {
                $this->line('   No users found with Pushover configured');
                
                if ($this->option('create-settings')) {
                    $user = User::first();
                    if ($user && env('PUSHOVER_USER_KEY') && env('PUSHOVER_API_TOKEN')) {
                        $this->line("   Creating test settings for user: {$user->id}");
                        
                        NotificationSettings::updateOrCreate(
                            ['user_id' => $user->id],
                            [
                                'pushover_enabled' => true,
                                'pushover_user_key' => env('PUSHOVER_USER_KEY'),
                                'pushover_api_token' => env('PUSHOVER_API_TOKEN'),
                            ]
                        );
                        
                        $this->line('   ✅ Test settings created');
                        Log::channel('notifications')->info('Test notification settings created', [
                            'user_id' => $user->id,
                        ]);
                    } else {
                        $this->error('   Cannot create settings - no user or missing credentials');
                        return null;
                    }
                } else {
                    $this->warn('   Use --create-settings to create test configuration');
                    return null;
                }
            }
        }
        
        $this->newLine();
        return $user;
    }

    /**
     * Test Pushover API directly.
     */
    private function testDirectPushoverAPI(): void
    {
        $this->info('4. TESTING PUSHOVER API DIRECTLY:');
        
        if (!env('PUSHOVER_USER_KEY') || !env('PUSHOVER_API_TOKEN')) {
            $this->warn('   Skipping - credentials not configured');
            return;
        }
        
        $message = 'Direct API test from ' . gethostname() . ' at ' . now()->format('Y-m-d H:i:s');
        
        Log::channel('notifications')->info('Manual Pushover test initiated via artisan command', [
            'user_key' => substr(env('PUSHOVER_USER_KEY'), 0, 8) . '...' . substr(env('PUSHOVER_USER_KEY'), -4),
            'api_token' => substr(env('PUSHOVER_API_TOKEN'), 0, 8) . '...' . substr(env('PUSHOVER_API_TOKEN'), -4),
            'message' => $message,
            'priority' => 0,
            'environment' => config('app.env'),
        ]);
        
        try {
            $response = Http::asForm()->post('https://api.pushover.net/1/messages.json', [
                'token' => env('PUSHOVER_API_TOKEN'),
                'user' => env('PUSHOVER_USER_KEY'),
                'message' => $message,
                'title' => 'Production API Test',
                'priority' => 0,
            ]);
            
            Log::channel('notifications')->info('Manual Pushover test API response', [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'successful' => $response->successful(),
            ]);
            
            if ($response->successful()) {
                $this->line('   ✅ Direct API test: SUCCESS');
                Log::channel('notifications')->info('Manual Pushover test completed successfully');
            } else {
                $this->line('   ❌ Direct API test: FAILED (HTTP ' . $response->status() . ')');
                $this->line('   Response: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->line('   ❌ Direct API test: EXCEPTION (' . $e->getMessage() . ')');
            Log::channel('notifications')->error('Manual Pushover test exception', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);
        }
        
        $this->newLine();
    }

    /**
     * Test Laravel notification service.
     */
    private function testLaravelNotificationService(User $user): void
    {
        $this->info('5. TESTING LARAVEL NOTIFICATION SERVICE:');
        
        $settings = $user->notificationSettings;
        if (!$settings) {
            $this->error('   No notification settings found for user');
            return;
        }
        
        $this->line("   User: {$user->id}");
        $this->line('   Pushover enabled: ' . ($settings->pushover_enabled ? 'YES' : 'NO'));
        $this->line('   User key configured: ' . (!empty($settings->pushover_user_key) ? 'YES' : 'NO'));
        $this->line('   API token configured: ' . (!empty($settings->pushover_api_token) ? 'YES' : 'NO'));
        
        try {
            $notificationService = app(NotificationService::class);
            $notificationService->sendTestPushover($user);
            
            $this->line('   ✅ Laravel NotificationService test: SUCCESS');
            
        } catch (\Exception $e) {
            $this->line('   ❌ Laravel NotificationService test: FAILED');
            $this->line('   Error: ' . $e->getMessage());
            $this->line('   Exception: ' . get_class($e));
        }
    }
}