<?php

/**
 * ============================================================================
 *  SECURE SYSTEM AUTH HANDLER (License Client)
 *  *** DO NOT MODIFY THIS FILE ***
 * ============================================================================
 */

class SysAuthHandler {
    private $config;
    private $validationResult = null;
    private $status = 'invalid';
    private $daysRemaining = 0;

    public function __construct(array $config) {
        $this->config = $config;
        if (!is_dir($this->config['cache_dir'])) {
            @mkdir($this->config['cache_dir'], 0777, true);
        }
        $this->validate();
    }

    private function validate() {
        $cacheFile = rtrim($this->config['cache_dir'], '/') . '/sys_token.dat';
        $now = time();

        // Check if valid cache exists
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && isset($data['timestamp'])) {
                $age = $now - $data['timestamp'];

                // If cache is fresh
                if ($age < $this->config['cache_ttl'] && $data['status'] === 'valid') {
                    $this->setValidState($data);
                    return; // Cache valid, no need to hit API
                }

                // If cache is expired but within grace period (API might be down)
                if ($age < $this->config['offline_grace_period'] && $data['status'] === 'valid') {
                    $apiAvailable = $this->checkApiAvailability();
                    if (!$apiAvailable) {
                        $this->setValidState($data);
                        return;
                    }
                }
            }
        }

        // Call API
        if (empty($this->config['license_key'])) {
            $this->setInvalidState('No license key provided.');
            return;
        }

        $response = $this->callApi();

        if ($response && isset($response['success']) && $response['success'] === true && isset($response['data']['valid']) && $response['data']['valid'] === true) {
            $response['timestamp'] = $now;
            $response['status'] = 'valid'; // Internal cache marker
            @file_put_contents($cacheFile, json_encode($response));
            $this->setValidState($response);
        } else {
            @unlink($cacheFile); // Remove invalid cache
            $errorMessage = $response['data']['reason'] ?? $response['message'] ?? 'Invalid license or domain.';
            $this->setInvalidState($errorMessage);
        }
    }

    private function callApi() {
        $url = $this->config['api_url'];

        // Get actual IP address instead of 'detect' string
        $ip = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
        if ($ip === '::1') {
            $ip = '127.0.0.1';
        }

        $data = [
            'license_key' => $this->config['license_key'],
            'domain'      => $_SERVER['HTTP_HOST'] ?? '127.0.0.1',
            'ip'          => $ip,
            'webhook_url' => $this->config['webhook_url'] ?? null
        ];

        // Logging request data
        $logFile = dirname($this->config['cache_dir']) . '/license_debug.log';
        $logMessage = "[" . date('Y-m-d H:i:s') . "] REQUEST to: $url | DATA: " . json_encode($data) . "\n";

        // Ensure cURL is used for the request
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            // Add Accept: application/json header so Laravel server returns JSON instead of redirecting
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($ch);

            // Log curl error if any
            if(curl_errno($ch)){
                $logMessage .= "[" . date('Y-m-d H:i:s') . "] CURL ERROR: " . curl_error($ch) . "\n";
            }

            curl_close($ch);
        } else {
            // Fallback to file_get_contents
            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data),
                    'timeout' => 15
                ]
            ];
            $context  = stream_context_create($options);
            $result = @file_get_contents($url, false, $context);
        }

        $logMessage .= "[" . date('Y-m-d H:i:s') . "] RESPONSE: " . $result . "\n\n";
        @file_put_contents($logFile, $logMessage, FILE_APPEND);

        if ($result) {
            return json_decode($result, true);
        }
        return ['status' => 'error', 'message' => 'Unable to connect to license server.'];
    }

    private function checkApiAvailability() {
        if (!function_exists('curl_init')) return true; // Assume available if no curl
        $ch = curl_init($this->config['api_url']);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($code >= 200 && $code < 500);
    }

    private function setValidState($data) {
        $this->status = 'valid';
        $this->daysRemaining = $data['days_remaining'] ?? PHP_INT_MAX;
        $this->validationResult = ['status' => 'valid', 'data' => $data];
    }

    private function setInvalidState($message) {
        $this->status = 'invalid';
        $this->daysRemaining = 0;
        $this->validationResult = ['status' => 'invalid', 'message' => $message];
    }

    public function isValid() {
        return $this->status === 'valid';
    }

    public function getValidation() {
        return $this->validationResult;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getDaysRemaining() {
        return $this->daysRemaining;
    }
}
