<?php

namespace App\Services\Courier;

use App\Contracts\CourierServiceInterface;
use App\Contracts\OrderTransformerInterface;
use App\Services\Courier\Http\CourierHttpClient;
use App\DTOs\CourierResponseDTO;
use App\Exceptions\CourierValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SteadfastCourierService implements CourierServiceInterface
{
    public function __construct(
        private CourierHttpClient $httpClient,
        private OrderTransformerInterface $transformer
    ) {}

    public function createOrder(array $orderData): array
    {
        $this->validateOrderData($orderData);

        $transformedData = $this->transformer->transform($orderData);
        Log::debug('Transformed order data', $transformedData);
        $response = $this->httpClient->post('/create_order', $transformedData);

        Log::debug('API response', $response);

        return CourierResponseDTO::fromArray($response)->toArray();
    }

    public function createBulkOrder(array $orders): array
    {
        if (count($orders) > 500) {
            throw new CourierValidationException('Maximum 500 orders allowed per bulk request');
        }

        foreach ($orders as $index => $order) {
            $this->validateOrderData($order, "orders.{$index}");
        }

        $transformedOrders = $this->transformer->transformBulk($orders);
        $response = $this->httpClient->post('/create_order/bulk-order', [
            'data' => json_encode($transformedOrders)
        ]);

        return $response;
    }

    public function getStatusByConsignmentId(string $consignmentId): array
    {
        $response = $this->httpClient->get("/status_by_cid/{$consignmentId}");
        return CourierResponseDTO::fromArray($response)->toArray();
    }

    public function getStatusByInvoice(string $invoice): array
    {
        $response = $this->httpClient->get("/status_by_invoice/{$invoice}");
        return CourierResponseDTO::fromArray($response)->toArray();
    }

    public function getStatusByTrackingCode(string $trackingCode): array
    {
        $response = $this->httpClient->get("/status_by_trackingcode/{$trackingCode}");
        return CourierResponseDTO::fromArray($response)->toArray();
    }

    private function validateOrderData(array $data, string $prefix = ''): void
    {
        $rules = [
            'invoice' => 'required|string|max:100',
            'recipient_name' => 'required|string|max:100',
            'recipient_phone' => 'required|string|size:11',
            'recipient_address' => 'required|string|max:250',
            'cod_amount' => 'required|numeric|min:0',
            'alternative_phone' => 'nullable|string|size:11',
            'recipient_email' => 'nullable|email',
            'note' => 'nullable|string',
            'item_description' => 'nullable|string',
            'total_lot' => 'nullable|integer|min:1',
            'delivery_type' => 'nullable|integer|in:0,1',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new CourierValidationException(
                'Validation failed for ' . ($prefix ?: 'order data'),
                $validator->errors()->toArray()
            );
        }
    }
}
