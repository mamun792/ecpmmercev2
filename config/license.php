<?php


# 3. Update your license config file (config/license.php)
return [
    'key' => env('SYSTEM_LICENSE_KEY', ''),
    'domain' => env('SYSTEM_DOMAIN', ''),
    'ip' => env('LICENSE_IP', ''),
    'url' => env('SYSTEM_CHECK_URL', 'https://licenc.auxdemo.com/api/licenses/validate'),
    'timeout' => env('LICENSE_REQUEST_TIMEOUT', 20),
    'connect_timeout' => env('LICENSE_REQUEST_CONNECT_TIMEOUT', 10),
    'allow_fallback' => env('LICENSE_ALLOW_FALLBACK', false), // Add this new option
];
