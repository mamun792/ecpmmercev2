<?php

namespace App\Services\Couriers\Pathao;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PathaoSuccessRateService
{
    private $baseUrl;
    private $accessToken;

    public function __construct()
    {
        $this->baseUrl = 'https://api-hermes.pathao.com';
    }

    public function getAccessToken()
    {
        // Check if we already have a valid token in memory
        if ($this->accessToken) {
            return $this->accessToken;
        }

        // Generate new token using environment variables
        $endpoint = '/aladdin/api/v1/issue-token';

        $payload = [
            'client_id' => env('PATHAO_CLIENT_ID'),
            'client_secret' => env('PATHAO_CLIENT_SECRET'),
            'username' => env('PATHAO_USERNAME'),
            'password' => env('PATHAO_PASSWORD'),
            'grant_type' => 'password'
        ];

        $response = $this->makeRequest('POST', $endpoint, $payload, false);

        if (isset($response['error'])) {
            throw new \Exception('Failed to get access token: ' . $response['error_description']);
        }

        if (!isset($response['access_token'])) {
            throw new \Exception('Failed to get access token');
        }

        $this->accessToken = $response['access_token'];
        Log::info('Pathao access token generated for success rate check');

        return $this->accessToken;
    }

    private function makeRequest($method, $endpoint, $payload = [], $withAuth = true)
    {
        // If endpoint starts with "http", use it as-is; otherwise prepend base URL
        $url = str_starts_with($endpoint, 'http') ? $endpoint : $this->baseUrl . $endpoint;

        $headers = [
            'Accept' => 'application/json',
        ];

        if ($method !== 'GET') {
            $headers['Content-Type'] = 'application/json';
        }

        if ($withAuth) {
            if (empty($this->accessToken)) {
                $this->getAccessToken();
            }
            $headers['Authorization'] = 'Bearer ' . $this->accessToken;
        }

        $client = new \GuzzleHttp\Client();

        $options = [
            'headers' => $headers,
        ];

        if ($method === 'GET') {
            $options['query'] = $payload;
        } else {
            $options['json'] = $payload;
        }

        try {
            $response = $client->request($method, $url, $options);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $errorBody = json_decode($response->getBody()->getContents(), true);

            // If token expired, try to refresh and retry once
            if ($withAuth && isset($errorBody['error']) && $errorBody['error'] === 'invalid_token') {
                $this->accessToken = null;
                $this->getAccessToken();
                return $this->makeRequest($method, $endpoint, $payload, true);
            }

            throw new \Exception('Pathao API Error: ' . json_encode($errorBody));
        }
    }

    public function successRate($phone)
    {
        return $this->makeRequest('GET', '/aladdin/api/v1/user/success', [
            'phone' => $phone
        ]);
    }
}