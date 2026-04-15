<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
$app = require_once __DIR__.'/../bootstrap/app.php';

// ╔═══════════════════════════════════════════════════════════════╗
// ║  SECURE LICENSE CHECK                                       ║
// ╚═══════════════════════════════════════════════════════════════╝
$sysHandler = __DIR__.'/../.sys_core/sys_auth_handler.php';
if (!file_exists($sysHandler)) {
    die("System UI Core Missing. Error: #SYS-001");
}
require_once $sysHandler;

$envPath = __DIR__ . '/../.env';
$licenseKey = null;
$apiUrl = 'http://127.0.0.1:8001/api/licenses/validate';
$gracePeriod = 604800; // 7 days in production

if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    if (preg_match('/^SYSTEM_LICENSE_KEY=(.*)$/m', $envContent, $matches)) {
        $licenseKey = trim($matches[1], "\"' ");
    }
    if (preg_match('/^SYSTEM_CHECK_URL=(.*)$/m', $envContent, $matches)) {
        $apiUrl = trim($matches[1], "\"' ");
    }
    if (preg_match('/^APP_ENV=(.*)$/m', $envContent, $matches)) {
        $env = trim($matches[1], "\"' ");
        // Reduce grace period in production to 1 day
        $gracePeriod = ($env === 'production') ? 86400 : 604800;
    }
}

// Ensure license key is set
if (empty($licenseKey)) {
    die("SYSTEM_LICENSE_KEY not found in .env file. Error: #SYS-002");
}

// স্বয়ংক্রিয়ভাবে ক্লায়েন্টের ডোমেইন থেকে webhook URL তৈরি করা হচ্ছে
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$webhookUrl = $protocol . $host . '/api/license-webhook';

$license = new SysAuthHandler([
    'license_key'          => $licenseKey,
    'api_url'              => $apiUrl,
    'webhook_url'          => $webhookUrl,
    'cache_dir'            => __DIR__ . '/../storage/framework/cache/.sys_cache',
    'cache_ttl'            => 86400,
    'offline_grace_period' => $gracePeriod,
]);

if (!$license->isValid()) {
    $validation = $license->getValidation();
    $errorCode  = $validation['data']['code'] ?? $validation['code'] ?? 'UNKNOWN';
    $errorMsg   = $validation['data']['message'] ?? $validation['message'] ?? 'License verification failed.';
    $errorMsg   = is_array($errorMsg) ? current($errorMsg) : $errorMsg; // make sure it's writable
    require __DIR__ . '/../resources/views/errors/license-invalid.php';
    exit;
}
// ╚═══════════════════════════════════════════════════════════════╝

$app->handleRequest(Request::capture());
