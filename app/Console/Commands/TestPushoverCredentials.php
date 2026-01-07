<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NotificationSettings;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class TestPushoverCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pushover:test {--user-id= : Test with specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Pushover credentials using the new environment priority system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== PUSHOVER CREDENTIALS TEST ===');
        $this->info('Environment: ' . config('app.env'));
        $this->newLine();

        // Show environment configuration
        $envUserKey = env('PUSHOVER_USER_KEY');
        $envApiToken = env('PUSHOVER_API_TOKEN');

        $this->info('Environment Variables:');
        $this->line('  PUSHOVER_USER_KEY: ' . ($envUserKey ? 'SET (' . strlen($envUserKey) . ' chars)' : 'NOT SET'));
        $this->line('  PUSHOVER_API_TOKEN: ' . ($envApiToken ? 'SET (' . strlen($envApiToken) . ' chars)' : 'NOT SET'));
        $this->newLine();

        // Find users with Pushover enabled
        $query = NotificationSettings::where('pushover_enabled', true);
        
        if ($userId = $this->option('user-id')) {
            $query->where('user_id', $userId);
        }
        
        $pushoverUsers = $query->with('user')->get();

        if ($pushoverUsers->isEmpty()) {
            $this->warn('No users found with Pushover enabled.');
            $this->line('Enable Pushover for users through the web interface first.');
            return Command::FAILURE;
        }

        $this->info("Found {$pushoverUsers->count()} user(s) with Pushover enabled:");
        $this->newLine();

        foreach ($pushoverUsers as $settings) {
            $userName = $settings->user->name ?? 'No name';
            $this->line("User ID {$settings->user_id} ({$userName}):");
            
            // Show credential source
            $credentialSource = $settings->getPushoverCredentialSource();
            $this->line("  Credential source: " . strtoupper($credentialSource));
            
            // Show effective credentials
            $effectiveUserKey = $settings->getEffectivePushoverUserKey();
            $effectiveApiToken = $settings->getEffectivePushoverApiToken();
            
            $this->line("  Effective credentials:");
            $userKeyDisplay = $effectiveUserKey ? substr($effectiveUserKey, 0, 8) . '...' . substr($effectiveUserKey, -4) : 'NOT SET';
            $apiTokenDisplay = $effectiveApiToken ? substr($effectiveApiToken, 0, 8) . '...' . substr($effectiveApiToken, -4) : 'NOT SET';
            $this->line("    User Key: {$userKeyDisplay}");
            $this->line("    API Token: {$apiTokenDisplay}");
            
            // Check if effectively enabled
            $effectivelyEnabled = $settings->isPushoverEffectivelyEnabled();
            $this->line("  Effectively enabled: " . ($effectivelyEnabled ? 'YES' : 'NO'));
            
            if ($effectivelyEnabled) {
                // Ask if user wants to send test notification
                if ($this->confirm("Send test notification to User ID {$settings->user_id}?", true)) {
                    try {
                        $notificationService = app(NotificationService::class);
                        $notificationService->sendTestPushover($settings->user);
                        $this->info("  ✅ Test notification sent successfully!");
                    } catch (\Exception $e) {
                        $this->error("  ❌ Test failed: " . $e->getMessage());
                        $this->line("  Check logs with: ddev artisan pail");
                    }
                }
            } else {
                $this->warn("  ⚠️  Cannot send test - missing valid credentials");
            }
            
            $this->newLine();
        }

        // Show system explanation
        $this->info('=== SYSTEM BEHAVIOR ===');
        $this->line('The new environment priority system:');
        $this->line('1. Checks .env for PUSHOVER_USER_KEY and PUSHOVER_API_TOKEN');
        $this->line('2. If both are set and valid (30 chars), uses them for ALL users');
        $this->line('3. Otherwise, falls back to individual database credentials');
        $this->line('4. No manual sync scripts needed - automatic priority!');
        $this->newLine();

        if ($envUserKey && $envApiToken) {
            $this->info('✅ Environment credentials configured - all users will use them automatically');
        } else {
            $this->warn('⚠️  No environment credentials - using individual database credentials');
            $this->line('To use environment priority: set PUSHOVER_USER_KEY and PUSHOVER_API_TOKEN in .env');
        }

        return Command::SUCCESS;
    }
}