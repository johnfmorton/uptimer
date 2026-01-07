<?php

/**
 * Production Pushover Debugging Script
 * 
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
echo "   PUSHOVER_USER_KEY: " . (env('PUSHOVER_USER_KEY') ? 'SET (' . strlen(env('PUSHOVER_USER_KEY')) . ' chars)' : 'NOT SET') . "\n";
echo "   PUSHOVER_API_TOKEN: " . (env('PUSHOVER_API_TOKEN') ? 'SET (' . strlen(env('PUSHOVER_API_TOKEN')) . ' chars)' : 'NOT SET') . "\n\n";

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
        $firstPushoverUser = \App\Models\NotificationSettings::where('pushover_enabled', true)->first();
        echo "   First Pushover user ID: {$firstPushoverUser->user_id}\n";
        echo "   Has user key: " . (!empty($firstPushoverUser->pushover_user_key) ? 'YES' : 'NO') . "\n";
        echo "   Has API token: " . (!empty($firstPushoverUser->pushover_api_token) ? 'YES' : 'NO') . "\n";
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

// 4. Laravel HTTP Client Test
echo "4. LARAVEL HTTP CLIENT TEST:\n";
if (env('PUSHOVER_USER_KEY') && env('PUSHOVER_API_TOKEN')) {
    try {
        echo "   Sending test notification via Laravel HTTP client...\n";
        
        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->asForm()
            ->post('https://api.pushover.net/1/messages.json', [
                'token' => env('PUSHOVER_API_TOKEN'),
                'user' => env('PUSHOVER_USER_KEY'),
                'message' => 'Production debug test from ' . gethostname() . ' at ' . date('Y-m-d H:i:s'),
                'title' => 'Production Debug Test',
                'priority' => 0,
            ]);
        
        echo "   HTTP Status: " . $response->status() . "\n";
        echo "   Response: " . $response->body() . "\n";
        echo "   Success: " . ($response->successful() ? 'YES' : 'NO') . "\n";
        
        if ($response->successful()) {
            echo "   ✅ Laravel HTTP client test PASSED\n";
        } else {
            echo "   ❌ Laravel HTTP client test FAILED\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Exception: " . $e->getMessage() . "\n";
        echo "   Exception Class: " . get_class($e) . "\n";
    }
} else {
    echo "   Skipping - Pushover credentials not configured in environment\n";
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
        echo "   No users found with Pushover properly configured\n";
        
        // Try to create a test configuration
        $firstUser = \App\Models\User::first();
        if ($firstUser && env('PUSHOVER_USER_KEY') && env('PUSHOVER_API_TOKEN')) {
            echo "   Creating test notification settings for user {$firstUser->id}...\n";
            
            $settings = \App\Models\NotificationSettings::updateOrCreate(
                ['user_id' => $firstUser->id],
                [
                    'pushover_enabled' => true,
                    'pushover_user_key' => env('PUSHOVER_USER_KEY'),
                    'pushover_api_token' => env('PUSHOVER_API_TOKEN'),
                ]
            );
            
            echo "   Testing with created settings...\n";
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->sendTestPushover($firstUser);
            
            echo "   ✅ Laravel NotificationService test PASSED (with created settings)\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Laravel NotificationService test FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Exception Class: " . get_class($e) . "\n";
}
echo "\n";

// 6. Log File Check
echo "6. LOG FILE STATUS:\n";
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

// 7. System Information
echo "7. SYSTEM INFORMATION:\n";
echo "   Operating System: " . php_uname('s r') . "\n";
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

echo "NEXT STEPS:\n";
echo "1. If network tests fail, check firewall rules for outbound HTTPS (port 443)\n";
echo "2. If credentials are missing, add PUSHOVER_USER_KEY and PUSHOVER_API_TOKEN to .env\n";
echo "3. If Laravel tests fail but direct tests pass, check notification settings in database\n";
echo "4. Check notification logs: php artisan notifications:logs\n";
echo "5. Test via web interface if available\n\n";

echo "COMMON PRODUCTION ISSUES:\n";
echo "- Missing environment variables in production .env file\n";
echo "- Firewall blocking outbound HTTPS connections\n";
echo "- Different user/database in production vs local\n";
echo "- SSL certificate validation issues\n";
echo "- Proxy server blocking requests\n";