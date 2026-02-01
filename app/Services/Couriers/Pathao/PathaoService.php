<?php

namespace App\Services\Couriers\Pathao;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\CourierSetting;
use Illuminate\Support\Facades\Log;

class PathaoService
{
    private $baseUrl;
    private $accessToken;

    public function __construct()
    {
        $this->baseUrl = 'https://api-hermes.pathao.com';
    }

    public function getAccessToken()
    {
        $settings = CourierSetting::first();

        if (!$settings) {
            throw new \Exception('Courier settings not found in database');
        }

        // Check if token exists and is not expired
        if ($settings->access_token && $settings->expires_at && Carbon::now()->lt($settings->expires_at)) {
            $this->accessToken = $settings->access_token;
            return $this->accessToken;
        }

        // Generate new token
        $endpoint = '/aladdin/api/v1/issue-token';

        $payload = [
            'client_id' => $settings->client_id,
            'client_secret' => $settings->client_secret,
            'username' => $settings->username,
            'password' => $settings->password,
            'grant_type' => 'password'
        ];

        $response = $this->makeRequest('POST', $endpoint, $payload, false);

        if (isset($response['error'])) {
            throw new \Exception('Failed to get access token: ' . $response['error_description']);
        }

        if (!isset($response['access_token'])) {
            throw new \Exception('Failed to get access token');
        }

        // Update settings with new token and expiration
        $settings->update([
            'access_token' => $response['access_token'],
            'expires_at' => Carbon::now()->addSeconds($response['expires_in']),
        ]);

        $this->accessToken = $response['access_token'];
        Log::info('Pathao access token updated: ' . $this->accessToken);
        return $this->accessToken;
    }

    private function makeRequest($method, $endpoint, $payload = [], $withAuth = true)
    {
        // If endpoint starts with "http", use it as-is; otherwise prepend base URL
        $url = str_starts_with($endpoint, 'http') ? $endpoint : $this->baseUrl . $endpoint;

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($withAuth) {
            if (empty($this->accessToken)) {
                $this->getAccessToken();
            }
            $headers['Authorization'] = 'Bearer ' . $this->accessToken;
        }

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request($method, $url, [
                'headers' => $headers,
                'json' => $payload,
            ]);

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


    public function getCities()
    {
        return $this->makeRequest('GET', '/aladdin/api/v1/city-list');
    }

    public function getZonesByCity($cityId)
    {
        return $this->makeRequest('GET', "/aladdin/api/v1/cities/{$cityId}/zone-list");
    }

    public function getAreasByZone($zoneId)
    {
        return $this->makeRequest('GET', "/aladdin/api/v1/zones/{$zoneId}/area-list");
    }

    public function getOrder($orderId)
    {
        return $this->makeRequest('GET', "/aladdin/api/v1/orders/{$orderId}");
    }

    public function calculateDeliveryFee($data)
    {
        return $this->makeRequest('POST', '/aladdin/api/v1/calculator/price', $data);
    }

    public function successRate($phone)
    {
        return $this->makeRequest('POST', 'https://merchant.pathao.com/api/v1/user/success', [
            'phone' => $phone
        ]);
    }


    public function createShipment($validated, $specialInstruction)
    {
        $order = Order::with(['items.product', 'items.productVariation'])->find($validated['orderId']);

        if (!$order) {
            throw new \Exception('Order not found', 404);
        }

        $settings = CourierSetting::first();
        if (!$settings) {
            throw new \Exception('Courier settings not found', 404);
        }

        $itemQuantity = $order->items->sum('quantity');
        $itemWeight = $order->items->sum(function ($item) {
            return $item->quantity * 0.5;
        });

        $itemDescription = $order->items->map(function ($item) {
            return "{$item->product->name} (Qty: {$item->quantity})";
        })->implode(', ');

        $itemDescription = $itemDescription ?: 'Cloth items';

        $payload = [
            'store_id' => $settings->StoreId,
            'merchant_order_id' => $order->order_number,
            'recipient_name' => $order->customer_name ?? 'Unknown Customer',
            'recipient_phone' => $order->customer_phone ?? '01887573015',
            'recipient_address' => $order->shipping_address ?? 'Default Address',
            'recipient_city' => $validated['recipient_city'],
            'recipient_zone' => $validated['recipient_zone'],
            'recipient_area' => $validated['recipient_area'],
            'delivery_type' => 48,
            'item_type' => 2,
            'special_instruction' => $specialInstruction,
            'item_quantity' => $itemQuantity,
            'item_weight' => $itemWeight,
            'item_description' => $itemDescription,
            'amount_to_collect' => $order->payment_status === 'paid' ? 0 : (float) $order->total,
        ];

        return $this->makeRequest('POST', '/aladdin/api/v1/orders', $payload);
    }



    public function updateOrderWithPathaoDetails($orderId, array $validated, array $responseData)
    {
        // Find the order
        $order = Order::findOrFail($orderId);

        // Update the order with consignment_id and other fields
        $order->update([
            'tracking_number' => $responseData['consignment_id'],
            'is_courier' => true,
            'courier_name' => 'pathao',
            'city_id' => $validated['recipient_city'],
            'zone_id' => $validated['recipient_zone'],
            'area_id' => $validated['recipient_area'],
            'city_name' => $responseData['city_name'] ?? null,
            'zone_name' => $responseData['zone_name'] ?? null,
            'area_name' => $responseData['area_name'] ?? null,
        ]);

        return $order;
    }




    public function createBulkShipment($validated)
    {
        $settings = CourierSetting::first();
        if (!$settings) {
            throw new \Exception('Courier settings not found', 404);
        }

        $ordersPayload = [];
        foreach ($validated['orders'] as $orderData) {
            $order = Order::with(['items.product', 'items.productVariation'])->find($orderData['orderId']);

            if (!$order) {
                throw new \Exception("Order ID {$orderData['orderId']} not found", 404);
            }

            $itemQuantity = $order->items->sum('quantity');
            $itemWeight = $order->items->sum(function ($item) {
                return $item->quantity * 0.5;
            });

            $itemDescription = $order->items->map(function ($item) {
                return "{$item->product->name} (Qty: {$item->quantity})";
            })->implode(', ');

            $itemDescription = $itemDescription ?: 'Cloth items';

            $ordersPayload[] = [
                'store_id' => $settings->StoreId,
                'merchant_order_id' => $order->order_number,
                'recipient_name' => $order->customer_name ?? 'Unknown Customer',
                'recipient_phone' => $order->customer_phone ?? '01887573015',
                'recipient_address' => $order->shipping_address ?? 'Default Address',
                'recipient_city' => $order->city_id,
                'recipient_zone' => $order->zone_id,
                'recipient_area' => $order->area_id,
                'delivery_type' => 48,
                'item_type' => 2,
                'special_instruction' => $orderData['specialInstruction'] ?? 'Need to Delivery before 5 PM',
                'item_quantity' => $itemQuantity,
                'item_weight' => $itemWeight,
                'amount_to_collect' => $order->payment_status === 'paid' ? 0 : (float) $order->total,
                'item_description' => $itemDescription,
            ];
        }

        $payload = ['orders' => $ordersPayload];

        return $this->makeRequest('POST', '/aladdin/api/v1/orders/bulk', $payload);
    }
}
