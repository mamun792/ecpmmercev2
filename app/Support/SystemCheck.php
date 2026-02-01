<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Exception;

class  SystemCheck
{
    /**
     * Check if the application license is valid
     *
     * @return bool
     */
    public static function isLicenseValid(): bool
    {
        // First check cache to avoid unnecessary API calls
        if (Cache::has('license_status')) {
            return Cache::get('license_status') === true;
        }

        $response = self::performCheck();

        if ($response === false) {
            self::setApplicationLicenseStatus(false);
            return false;
        }

        // Check if response is valid and license is active
        if (isset($response['success']) && $response['success'] === true) {
            // Check for nested "valid" field in "data"
            if (isset($response['data']['valid']) && $response['data']['valid'] === true) {
                // License is valid
                Cache::put('license_status', true, now()->addminutes(5));
                self::setApplicationLicenseStatus(true);
                return true;
            } else {
                // Response was successful but license is invalid
                $reason = $response['data']['reason'] ?? 'Unknown reason';
                Log::warning('License validation failed', ['reason' => $reason]);
                self::setApplicationLicenseStatus(false);
                return false;
            }
        }

        self::setApplicationLicenseStatus(false);
        return false;
    }

    /**
     * Perform the actual license validation check with the central server
     *
     * @return array|bool Response from license server or false on failure
     */
    private static function performCheck()
    {
        Log::info('Performing license validation check');

        try {
            // Check if required environment variables are set
            if (empty(config('license.key')) || empty(config('license.domain'))) {
                Log::error('License key or domain not set in config');
                return false;
            }

            $payload = [
                'license_key' => config('license.key'),
                'domain'      => config('license.domain'),
                'ip'          => config('license.ip', '')
            ];

            Log::debug('License validation payload', ['payload' => $payload]);

            $response = Http::timeout(config('license.timeout', 30))
                ->post(config('license.url'), $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('License check result', [
                    'status' => $data['success'] ?? false,
                    'valid' => $data['data']['valid'] ?? false,
                    'reason' => $data['data']['reason'] ?? 'No reason provided'
                ]);
                return $data;
            }

            Log::error('License server returned error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error'   => 'License server returned status code: ' . $response->status(),
            ];
        } catch (Exception $e) {
            Log::error('License validation error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Set the application license status
     *
     * @param bool $isValid Whether the license is valid
     * @return void
     */
    private static function setApplicationLicenseStatus(bool $isValid): void
    {
        // Store status in cache
        Cache::put('app_status', $isValid ? 'online' : 'offline', now()->addMinutes(5));

        // Update the license status file
        $statusFile = storage_path('app/license_status.json');

        $status = [
            'valid' => $isValid,
            'updated_at' => now()->toIso8601String(),
            'message' => $isValid
                ? 'License is valid'
                : 'License validation failed. Please contact support.',
        ];

        File::put($statusFile, json_encode($status, JSON_PRETTY_PRINT));

        Log::info('Application license status updated', ['status' => $isValid ? 'VALID' : 'INVALID']);
    }

    /**
     * Check if application should be accessible based on license status
     *
     * @return bool
     */
    public static function isApplicationAccessible(): bool
    {
        // Check if we have forced override for admins
        if (Cache::has('license_admin_override') && Cache::get('license_admin_override') === true) {
            Log::info('License check bypassed due to admin override');
            return true;
        }

        // Check current license status
        $statusFile = storage_path('app/license_status.json');

        if (File::exists($statusFile)) {
            $status = json_decode(File::get($statusFile), true);

            // If license is valid, allow access
            if (isset($status['valid']) && $status['valid'] === true) {
                return true;
            }

            // If license was checked recently and is invalid, block access
            if (isset($status['updated_at'])) {
                $updatedAt = new \DateTime($status['updated_at']);
                $now = new \DateTime();

                // If last check was less than 5 minutes ago, use cached result
                if ($updatedAt->diff($now)->i < 5) {
                    return false;
                }
            }
        }

        // If we're here, we need to check the license again
        return self::isLicenseValid();
    }

    /**
     * Enable admin override of license check for a specific time
     * This can be used by admins to access the system even with invalid license
     *
     * @param int $minutes Minutes to enable override for
     * @return void
     */
    public static function enableAdminOverride(int $minutes = 60): void
    {
        Cache::put('license_admin_override', true, now()->addMinutes($minutes));
        Log::warning('License admin override enabled for ' . $minutes . ' minutes');
    }

    /**
     * Run periodic license check from scheduler
     * This can be called from the Laravel scheduler
     *
     * @return void
     */
    public static function runScheduledCheck(): void
    {
        Log::info('Running scheduled license check');
        self::isLicenseValid();
    }
}
