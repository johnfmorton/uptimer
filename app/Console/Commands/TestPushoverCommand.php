<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestPushoverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pushover:test 
                            {--user-key= : Pushover user key (optional, uses config if not provided)}
                            {--api-token= : Pushover API token (optional, uses config if not provided)}
                            {--message= : Custom message (optional)}
                            {--priority=0 : Priority level (0=normal, 1=high, 2=emergency)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Pushover notification configuration and connectivity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔔 Testing Pushover Configuration');
        $this->newLine();

        // Get credentials from options or config
        $user_key = $this->option('user-key') ?: config('services.pushover.user_key');
        $api_token = $this->option('api-token') ?: config('services.pushover.token');
        
        // Validate credentials
        if (empty($user_key)) {
            $this->error('❌ Pushover user key not configured');
            $this->line('   Set PUSHOVER_USER_KEY in .env or use --user-key option');
            return Command::FAILURE;
        }
        
        if (empty($api_token)) {
            $this->error('❌ Pushover API token not configured');
            $this->line('   Set PUSHOVER_API_TOKEN in .env or use --api-token option');
            return Command::FAILURE;
        }

        // Display configuration (masked)
        $this->info('Configuration:');
        $this->line("   User Key: " . substr($user_key, 0, 8) . '...' . substr($user_key, -4));
        $this->line("   API Token: " . substr($api_token, 0, 8) . '...' . substr($api_token, -4));
        $this->line("   Environment: " . config('app.env'));
        $this->newLine();

        // Test network connectivity first
        $this->info('🌐 Testing Network Connectivity...');
        
        // DNS resolution test
        $ip = gethostbyname('api.pushover.net');
        if ($ip === 'api.pushover.net') {
            $this->error('❌ DNS resolution failed for api.pushover.net');
            return Command::FAILURE;
        }
        $this->line("   ✅ DNS resolved: api.pushover.net → $ip");

        // Basic HTTPS connectivity test
        try {
            $response = Http::timeout(10)->get('https://api.pushover.net');
            $this->line("   ✅ HTTPS connectivity successful (HTTP {$response->status()})");
        } catch (\Exception $e) {
            $this->error("   ❌ HTTPS connectivity failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->newLine();

        // Prepare test message
        $message = $this->option('message') ?: 'Test notification from ' . config('app.name') . ' at ' . now()->format('Y-m-d H:i:s');
        $priority = (int) $this->option('priority');
        
        $payload = [
            'token' => $api_token,
            'user' => $user_key,
            'message' => $message,
            'title' => 'Test Notification',
            'priority' => $priority,
        ];

        // Add emergency priority parameters if needed
        if ($priority === 2) {
            $payload['expire'] = 3600; // 1 hour
            $payload['retry'] = 60;    // 1 minute
        }

        $this->info('📤 Sending Test Notification...');
        $this->line("   Message: $message");
        $this->line("   Priority: $priority");
        
        // Log the attempt
        Log::channel('notifications')->info('Manual Pushover test initiated via artisan command', [
            'user_key' => substr($user_key, 0, 8) . '...' . substr($user_key, -4),
            'api_token' => substr($api_token, 0, 8) . '...' . substr($api_token, -4),
            'message' => $message,
            'priority' => $priority,
            'environment' => config('app.env'),
        ]);

        try {
            $response = Http::timeout(30)->asForm()->post('https://api.pushover.net/1/messages.json', $payload);
            
            // Log detailed response
            Log::channel('notifications')->info('Manual Pushover test API response', [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'successful' => $response->successful(),
            ]);

            $this->newLine();
            $this->info('📋 API Response:');
            $this->line("   Status Code: " . $response->status());
            $this->line("   Response: " . $response->body());

            if ($response->successful()) {
                $this->newLine();
                $this->info('✅ Pushover notification sent successfully!');
                $this->line('   Check your Pushover app for the test notification.');
                
                Log::channel('notifications')->info('Manual Pushover test completed successfully');
                return Command::SUCCESS;
            } else {
                $this->newLine();
                $this->error('❌ Pushover API returned an error');
                
                // Parse response for specific error details
                $response_data = $response->json();
                if (isset($response_data['errors'])) {
                    foreach ($response_data['errors'] as $error) {
                        $this->line("   Error: $error");
                    }
                }
                
                Log::channel('notifications')->error('Manual Pushover test failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Exception occurred: ' . $e->getMessage());
            $this->line('   Exception class: ' . get_class($e));
            
            Log::channel('notifications')->error('Manual Pushover test exception', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);
            
            return Command::FAILURE;
        }
    }
}
