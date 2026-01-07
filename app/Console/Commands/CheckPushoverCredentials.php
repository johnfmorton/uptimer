<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckPushoverCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pushover:check {--test : Send a test notification to the first available user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Pushover credential configuration (environment-first system)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== PUSHOVER CREDENTIALS CHECK (Environment-First System) ===');
        $this->info('Environment: ' . config('app.env'));
        $this->info('Time: ' . now()->format('Y-m-d H:i:s T'));
        $this->newLine();

        // Check environment variables
        $this->checkEnvironmentCredentials();
        
        // Check database settings
        $this->checkDatabaseSettings();
        
        // Show how the system works
        $this->explainSystem();
        
        // Test if requested
        if ($this->option('test')) {
            $this->testNotification();
        }
        
        // Migration guidance
        $this->showMigrationGuidance();

        return Command::SUCCESS;
    }

    private function checkEnvironmentCredentials(): void
    {
        $this->info('1. ENVIRONMENT VARIABLES (.env file):');
        
        $envUserKey = env('PUSHOVER_USER_KEY');
        $envApiToken = env('PUSHOVER_API_TOKEN');
        
        $this->line('   PUSHOVER_USER_KEY: ' . ($envUserKey ? 'SET (' . strlen($envUserKey) . ' chars)' : 'NOT SET'));
        $this->line('   PUSHOVER_API_TOKEN: ' . ($envApiToken ? 'SET (' . strlen($envApiToken) . ' chars)' : 'NOT SET'));
        
        $envValid = $envUserKey && strlen($envUserKey) === 30 && $envApiToken && strlen($envApiToken) === 30;
        
        if ($envValid) {
            $this->line('   Environment credentials valid: <fg=green>✅ YES</>');
        } else {
            $this->line('   Environment credentials valid: <fg=red>❌ NO</>');
            
            if ($envUserKey && strlen($envUserKey) !== 30) {
                $this->warn('   ⚠️  PUSHOVER_USER_KEY should be 30 characters (got ' . strlen($envUserKey) . ')');
            }
            if ($envApiToken && strlen($envApiToken) !== 30) {
                $this->warn('   ⚠️  PUSHOVER_API_TOKEN should be 30 characters (got ' . strlen($envApiToken) . ')');
            }
        }
        
        $this->newLine();
    }

    private function checkDatabaseSettings(): void
    {
        $this->info('2. DATABASE NOTIFICATION SETTINGS:');
        
        try {
            $users = User::with('notificationSettings')->get();
            
            if ($users->count() === 0) {
                $this->line('   No users found in database.');
                $this->newLine();
                return;
            }
            
            foreach ($users as $user) {
                $settings = $user->notificationSettings;
                
                $this->line("   User #{$user->id} ({$user->email}):");
                
                if (!$settings) {
                    $this->line('     - No notification settings configured');
                    continue;
                }
                
                $this->line('     - Pushover enabled: ' . ($settings->pushover_enabled ? 'YES' : 'NO'));
                $this->line('     - Database user key: ' . (!empty($settings->pushover_user_key) ? 'SET (' . strlen($settings->pushover_user_key) . ' chars)' : 'NOT SET'));
                $this->line('     - Database API token: ' . (!empty($settings->pushover_api_token) ? 'SET (' . strlen($settings->pushover_api_token) . ' chars)' : 'NOT SET'));
                
                // Show effective credentials using new methods
                $effectiveUserKey = $settings->getEffectivePushoverUserKey();
                $effectiveApiToken = $settings->getEffectivePushoverApiToken();
                $credentialSource = $settings->getPushoverCredentialSource();
                $effectivelyEnabled = $settings->isPushoverEffectivelyEnabled();
                
                $this->line('     - Effective user key: ' . ($effectiveUserKey ? 'SET (' . strlen($effectiveUserKey) . ' chars)' : 'NOT SET'));
                $this->line('     - Effective API token: ' . ($effectiveApiToken ? 'SET (' . strlen($effectiveApiToken) . ' chars)' : 'NOT SET'));
                $this->line('     - Credential source: <fg=yellow>' . strtoupper($credentialSource) . '</>');
                
                if ($effectivelyEnabled) {
                    $this->line('     - Effectively enabled: <fg=green>✅ YES</>');
                    $this->line('     - User key preview: ' . substr($effectiveUserKey, 0, 8) . '...' . substr($effectiveUserKey, -4));
                    $this->line('     - API token preview: ' . substr($effectiveApiToken, 0, 8) . '...' . substr($effectiveApiToken, -4));
                } else {
                    $this->line('     - Effectively enabled: <fg=red>❌ NO</>');
                }
                
                $this->newLine();
            }
            
        } catch (\Exception $e) {
            $this->error('   ❌ Database error: ' . $e->getMessage());
            $this->newLine();
        }
    }

    private function explainSystem(): void
    {
        $this->info('3. HOW THE NEW SYSTEM WORKS:');
        $this->line('   Priority Order:');
        $this->line('   1. Environment variables (.env file) - <fg=green>HIGHEST PRIORITY</>');
        $this->line('   2. Database values (per user) - <fg=yellow>FALLBACK</>');
        $this->newLine();
        
        $this->line('   Benefits:');
        $this->line('   <fg=green>✅</> .env file is now the source of truth');
        $this->line('   <fg=green>✅</> No manual sync scripts needed');
        $this->line('   <fg=green>✅</> Automatic fallback to database if .env not configured');
        $this->line('   <fg=green>✅</> Per-user database credentials still work for multi-tenant setups');
        $this->line('   <fg=green>✅</> Easy production deployment - just update .env');
        $this->newLine();
    }

    private function testNotification(): void
    {
        $this->info('4. TESTING NOTIFICATION:');
        
        try {
            $testUser = User::whereHas('notificationSettings', function($query) {
                $query->where('pushover_enabled', true);
            })->first();
            
            if (!$testUser || !$testUser->notificationSettings) {
                $this->warn('   No users found with Pushover enabled');
                return;
            }
            
            $settings = $testUser->notificationSettings;
            
            $this->line("   Testing with User #{$testUser->id}:");
            $this->line('   Credential source: <fg=yellow>' . strtoupper($settings->getPushoverCredentialSource()) . '</>');
            $this->line('   Effectively enabled: ' . ($settings->isPushoverEffectivelyEnabled() ? 'YES' : 'NO'));
            
            if ($settings->isPushoverEffectivelyEnabled()) {
                $this->line('   Sending test notification...');
                
                try {
                    $notificationService = app(NotificationService::class);
                    $notificationService->sendTestPushover($testUser);
                    $this->line('   <fg=green>✅ Test notification sent successfully!</>');
                    
                } catch (\Exception $e) {
                    $this->error('   ❌ Test notification failed: ' . $e->getMessage());
                }
            } else {
                $this->warn('   ⚠️  Cannot test - Pushover not effectively enabled');
            }
            
        } catch (\Exception $e) {
            $this->error('   ❌ Testing error: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function showMigrationGuidance(): void
    {
        $this->info('5. MIGRATION GUIDANCE:');
        
        $envUserKey = env('PUSHOVER_USER_KEY');
        $envApiToken = env('PUSHOVER_API_TOKEN');
        $envValid = $envUserKey && strlen($envUserKey) === 30 && $envApiToken && strlen($envApiToken) === 30;
        
        if ($envValid) {
            $this->line('   <fg=green>✅</> Environment credentials are configured and valid');
            $this->line('   <fg=green>✅</> All users will automatically use environment credentials');
            $this->line('   <fg=green>✅</> No manual intervention needed');
            $this->line('   <fg=green>✅</> You can remove database credentials if desired (optional)');
        } else {
            $this->warn('   ⚠️  Environment credentials not configured');
            $this->line('   📝 To migrate to environment-first system:');
            $this->line('      1. Add PUSHOVER_USER_KEY=your_30_char_key to .env');
            $this->line('      2. Add PUSHOVER_API_TOKEN=your_30_char_token to .env');
            $this->line('      3. Deploy to production');
            $this->line('      4. All users will automatically use new credentials');
            $this->line('      5. Database credentials become backup/fallback');
        }
        
        $this->newLine();
        $this->info('Run with --test flag to send a test notification');
    }
}
