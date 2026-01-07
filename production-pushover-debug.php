<?php

/**
 * Production Pushover Debugging Script
 * 
 * Works with the database-based Pushover configuration system.
 * Upload this file to your production server and run it to debug Pushover issues.
 * Usage: php production-pushover-debug.php
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PRODUCTION PUSHOVER DEBUGGING ===\n";
echo "Server: " . gethostname() . "\n";
echo "Time: " . date('Y-m-d H:i:s T') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n\n";

// 1. Environment Check
echo "1. ENVIRONMENT CONFIGURATION:\n";
echo "   APP_ENV: " . env('APP_ENV', 'not set') . "\n";
echo "   APP_DEBUG: " . (env('APP_DEBUG') ? 'true' : 'false') . "\n";
echo "   LOG_LEVEL: " . env('LOG_LEVEL', 'not set') . "\n";
echo "   PUSHOVER_USER_KEY (env): " . (env('PUSHOVER_USER_KEY') ? 'SET (' . strlen(env('PUSHOVER_USER_KEY')) . ' chars)' : 'NOT SET') . "\n";
echo "   PUSHOVER_API_TOKEN (env): " . (env('PUSHOVER_API_TOKEN') ? 'SET (' . strlen(env('PUSHOVER_API_TOKEN')) . ' chars)' : 'NOT SET') . "\n";
echo "   NOTE: This app uses DATABASE-STORED credentials, not environment variables\n\n";

// 2. Database Check
echo "2. DATABASE & USER CHECK:\n";
try {
    $userCount = \App\Models\User::count();
    echo "   Total users: $userCount\n";
    
    $settingsCount = \App\Models\NotificationSettings::count();
    echo "   Notification settings records: $settingsCount\n";
    
    $pushoverEnabledCount = \App\Models\NotificationSettings::where('pushover_enabled', true)->count();
    echo "   Users with Pushover enabled: $pushoverEnabledCount\n";
    
    if ($pushoverEnabledCount > 0) {
        $pushoverUsers = \App\Models\NotificationSettings::where('pushover_enabled', true)
            ->whereNotNull('pushover_user_key')
            ->whereNotNull('pushover_api_token')
            ->get();
            
        echo "   Users with complete Pushover config: " . $pushoverUsers->count() . "\n";
        
        foreach ($pushoverUsers as $index => $settings) {
            echo "   User #{$settings->user_id}:\n";
            echo "     - User key length: " . strlen($settings->pushover_user_key) . " chars\n";
            echo "     - API token length: " . strlen($settings->pushover_api_token) . " chars\n";
            echo "     - User key preview: " . substr($settings->pushover_user_key, 0, 8) . "...\n";
            echo "     - API token preview: " . substr($settings->pushover_api_token, 0, 8) . "...\n";
            
            if ($index >= 2) { // Limit to first 3 users for brevity
                echo "   ... and " . ($pushoverUsers->count() - 3) . " more users\n";
                break;
            }
        }
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Network Connectivity
echo "3. NETWORK CONNECTIVITY:\n";

// DNS Resolution
echo "   Testing DNS resolution for api.pushover.net: ";
$dns_result = gethostbyname('api.pushover.net');
if ($dns_result !== 'api.pushover.net') {
    echo "SUCCESS ($dns_result)\n";
} else {
    echo "FAILED\n";
}

// HTTPS Connectivity
echo "   Testing HTTPS connectivity: ";
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET'
    ]
]);

$result = @file_get_contents('https://api.pushover.net', false, $context);
if ($result !== false) {
    echo "SUCCESS\n";
} else {
    echo "FAILED\n";
}

// cURL test
echo "   Testing cURL to Pushover API: ";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.pushover.net');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$curl_result = curl_exec($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curl_result !== false && $http_code === 200) {
    echo "SUCCESS (HTTP $http_code)\n";
} else {
    echo "FAILED (HTTP $http_code)";
    if ($curl_error) {
        echo " - $curl_error";
    }
    echo "\n";
}
echo "\n";

// 4. Test with actual database credentials
echo "4. TESTING WITH DATABASE-STORED CREDENTIALS:\n";
try {
    $testUser = \App\Models\User::whereHas('notificationSettings', function($query) {
        $query->where('pushover_enabled', true)
              ->whereNotNull('pushover_user_key')
              ->whereNotNull('pushover_api_token');
    })->first();
    
    if ($testUser && $testUser->notificationSettings) {
        $settings = $testUser->notificationSettings;
        
        echo "   Testing with User #{$testUser->id} credentials:\n";
        echo "   User key: " . substr($settings->pushover_user_key, 0, 8) . "..." . substr($settings->pushover_user_key, -4) . "\n";
        echo "   API token: " . substr($settings->pushover_api_token, 0, 8) . "..." . substr($settings->pushover_api_token, -4) . "\n";
        
        // Test with Laravel HTTP client using actual credentials
        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->asForm()
            ->post('https://api.pushover.net/1/messages.json', [
                'token' => $settings->pushover_api_token,
                'user' => $settings->pushover_user_key,
                'message' => 'Production debug test from ' . gethostname() . ' at ' . date('Y-m-d H:i:s'),
                'title' => 'Production Debug Test',
                'priority' => 0,
            ]);
        
        echo "   HTTP Status: " . $response->status() . "\n";
        echo "   Response: " . $response->body() . "\n";
        echo "   Success: " . ($response->successful() ? 'YES' : 'NO') . "\n";
        
        if ($response->successful()) {
            echo "   ✅ Database credentials test PASSED\n";
        } else {
            echo "   ❌ Database credentials test FAILED\n";
            
            // Parse response for specific error details
            $responseData = json_decode($response->body(), true);
            if (isset($responseData['errors'])) {
                echo "   Specific errors:\n";
                foreach ($responseData['errors'] as $error) {
                    echo "     - $error\n";
                }
            }
        }
        
    } else {
        echo "   No users found with complete Pushover configuration\n";
        
        // Show what we do have
        $allSettings = \App\Models\NotificationSettings::where('pushover_enabled', true)->get();
        if ($allSettings->count() > 0) {
            echo "   Found {$allSettings->count()} users with Pushover enabled but incomplete config:\n";
            foreach ($allSettings as $settings) {
                echo "     User #{$settings->user_id}: ";
                echo "user_key=" . (!empty($settings->pushover_user_key) ? 'SET' : 'MISSING') . ", ";
                echo "api_token=" . (!empty($settings->pushover_api_token) ? 'SET' : 'MISSING') . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   Exception Class: " . get_class($e) . "\n";
}
echo "\n";

// 5. Laravel Notification Service Test
echo "5. LARAVEL NOTIFICATION SERVICE TEST:\n";
try {
    $user = \App\Models\User::whereHas('notificationSettings', function($query) {
        $query->where('pushover_enabled', true)
              ->whereNotNull('pushover_user_key')
              ->whereNotNull('pushover_api_token');
    })->first();
    
    if ($user) {
        echo "   Found user with Pushover configured: {$user->id}\n";
        
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->sendTestPushover($user);
        
        echo "   ✅ Laravel NotificationService test PASSED\n";
        
    } else {
        echo "   No users found with complete Pushover configuration\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Laravel NotificationService test FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Exception Class: " . get_class($e) . "\n";
}
echo "\n";

// 6. Credential Validation
echo "6. CREDENTIAL VALIDATION:\n";
try {
    $pushoverUsers = \App\Models\NotificationSettings::where('pushover_enabled', true)
        ->whereNotNull('pushover_user_key')
        ->whereNotNull('pushover_api_token')
        ->get();
        
    foreach ($pushoverUsers as $settings) {
        echo "   User #{$settings->user_id}:\n";
        
        // Validate user key format (should be 30 characters)
        $userKeyLength = strlen($settings->pushover_user_key);
        echo "     User key length: $userKeyLength " . ($userKeyLength === 30 ? '✅' : '❌ (should be 30)') . "\n";
        
        // Validate API token format (should be 30 characters)
        $tokenLength = strlen($settings->pushover_api_token);
        echo "     API token length: $tokenLength " . ($tokenLength === 30 ? '✅' : '❌ (should be 30)') . "\n";
        
        // Check for common issues
        if (strpos($settings->pushover_user_key, ' ') !== false) {
            echo "     ❌ User key contains spaces\n";
        }
        if (strpos($settings->pushover_api_token, ' ') !== false) {
            echo "     ❌ API token contains spaces\n";
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Validation error: " . $e->getMessage() . "\n";
}

// 7. Log File Check
echo "7. LOG FILE STATUS:\n";
$logPath = storage_path('logs/notifications.log');
echo "   Notifications log path: $logPath\n";
echo "   Log file exists: " . (file_exists($logPath) ? 'YES' : 'NO') . "\n";
echo "   Log directory writable: " . (is_writable(dirname($logPath)) ? 'YES' : 'NO') . "\n";

if (file_exists($logPath)) {
    $logSize = filesize($logPath);
    echo "   Log file size: " . number_format($logSize) . " bytes\n";
    
    if ($logSize > 0) {
        echo "   Recent log entries (last 5 lines):\n";
        $lines = file($logPath);
        $recentLines = array_slice($lines, -5);
        foreach ($recentLines as $line) {
            echo "   " . trim($line) . "\n";
        }
    }
}
echo "\n";

// 8. System Information
echo "8. SYSTEM INFORMATION:\n";
echo "   Operating System: " . php_uname('s') . " " . php_uname('r') . "\n";
echo "   Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "   PHP Extensions:\n";
echo "     - cURL: " . (extension_loaded('curl') ? 'YES' : 'NO') . "\n";
echo "     - OpenSSL: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
echo "     - JSON: " . (extension_loaded('json') ? 'YES' : 'NO') . "\n";

if (extension_loaded('curl')) {
    $curl_version = curl_version();
    echo "     - cURL Version: " . $curl_version['version'] . "\n";
    echo "     - SSL Version: " . $curl_version['ssl_version'] . "\n";
}
echo "\n";

echo "=== DEBUGGING COMPLETE ===\n\n";

echo "DIAGNOSIS:\n";
echo "This application stores Pushover credentials in the database per user,\n";
echo "not in environment variables. The original debug script was checking\n";
echo "for PUSHOVER_USER_KEY and PUSHOVER_API_TOKEN in .env, but your app\n";
echo "uses individual credentials stored in the notification_settings table.\n\n";

echo "NEXT STEPS:\n";
echo "1. Check if users have valid Pushover credentials in the database\n";
echo "2. Verify API tokens are 30 characters and valid\n";
echo "3. Ensure user keys are 30 characters and valid\n";
echo "4. Test credentials manually at https://pushover.net/\n";
echo "5. Check notification logs for detailed error information\n\n";

echo "COMMON ISSUES:\n";
echo "- Invalid or expired Pushover API tokens\n";
echo "- Incorrect user keys (not matching Pushover account)\n";
echo "- API tokens from wrong Pushover application\n";
echo "- Credentials with extra spaces or characters\n";
echo "- Pushover account limits exceeded\n";