<?php

namespace App\Http\Controllers\Api\Courier;

use App\DTOs\CourierResponseDTO;
use App\Exceptions\CourierServiceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Courier\CreateBulkOrderRequest;
use App\Http\Requests\Courier\CreateOrderRequest;
use App\Models\Order;
use App\Services\Courier\CourierManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CourierController extends Controller
{
    public function __construct(
        private CourierManager $courierManager
    ) {}




    public function createOrder(Request $request): JsonResponse
    {
        try {
            $id = $request->input('oders_id'); // Get ID from request body
            Log::info('Received order ID', ['id' => $id]);
            if (!$id) {
                return response()->json(['message' => 'Order ID is required'], 400);
            }

            $order = Order::findOrFail($id);
            $courierData = [
                'invoice' => $order->order_number,
                'recipient_name' => $order->customer_name,
                'recipient_phone' => $order->customer_phone,
                'recipient_address' => $order->shipping_address,
                'cod_amount' => $order->payment_status === 'paid' ? 0 : (float) $order->total,
                'recipient_email' => $order->customer_email,
                'note' => $order->customer_notes ?? 'Handle with care',
                'item_description' => 'Order items',
                'total_lot' => 1,
                'delivery_type' => 0,
            ];

            Log::debug('Courier data prepared', ['courierData' => $courierData]);

            $result = $this->courierManager->createOrder($courierData);
            $dto = CourierResponseDTO::fromArray($result);

            if (!$dto->isSuccess()) {
                Log::error('Courier order creation failed', [
                    'order_id' => $id,
                    'response' => $result
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $dto->message ?: 'Failed to create courier order',
                    'data' => $result
                ], $dto->status);
            }

            // Get the delivery status from the createOrder response
            $deliveryStatus = $result['consignment']['status'] ?? 'unknown';

            // Call getStatusByConsignmentId to get the latest status (optional)
            $statusResult = $this->courierManager->getStatusByConsignmentId($result['consignment']['consignment_id'] ?? '');
            $dtoStatus = CourierResponseDTO::fromArray($statusResult);
            if ($dtoStatus->isSuccess()) {
                $deliveryStatus = $dtoStatus->deliveryStatus ?? $dtoStatus->consignment['status'] ?? $deliveryStatus;
            }

            // Update the Order model with courier details
            $order->update([
                'courier_name' => 'steadfast',
                'tracking_number' => $result['consignment']['tracking_code'] ?? null,
                'consignment_id' => $result['consignment']['consignment_id'] ?? null,
                'is_courier' => true,
                'delivery_status' => $deliveryStatus,
            ]);

            Log::info('Courier order created successfully', [
                'order_id' => $id,
                'response' => $result
            ]);

            Cache::flush();

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (CourierServiceException $e) {
            Log::error('Courier order creation error', [
                'order_id' => $id,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        } catch (\Exception $e) {
            Log::error('Unexpected error in courier order creation', [
                'order_id' => $id,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }


    public function createBulkOrder(Request $request): JsonResponse
    {
        $orderIds = $request->input('orderIds', []);
        $responses = [];
        $errors = [];

        foreach ($orderIds as $orderId) {
            try {
                $order = Order::findOrFail($orderId);

                $courierData = [
                    'invoice' => $order->order_number,
                    'recipient_name' => $order->customer_name,
                    'recipient_phone' => $order->customer_phone,
                    'recipient_address' => $order->shipping_address,
                    'cod_amount' => $order->payment_status === 'paid' ? 0 : (float) $order->total,
                    'recipient_email' => $order->customer_email,
                    'note' => $order->customer_notes ?? 'Handle with care',
                    'item_description' => 'Order items',
                    'total_lot' => 1,
                    'delivery_type' => 0,
                ];

                Log::debug('Courier data for bulk order', ['order_id' => $orderId, 'data' => $courierData]);

                $result = $this->courierManager->createOrder($courierData);
                $dto = CourierResponseDTO::fromArray($result);

                if (!$dto->isSuccess()) {
                    $errors[] = [
                        'order_id' => $orderId,
                        'message' => $dto->message ?? 'Failed to create courier order',
                        'response' => $result
                    ];
                    continue;
                }

                $deliveryStatus = $result['consignment']['status'] ?? 'unknown';

                $statusResult = $this->courierManager->getStatusByConsignmentId($result['consignment']['consignment_id'] ?? '');
                $dtoStatus = CourierResponseDTO::fromArray($statusResult);
                if ($dtoStatus->isSuccess()) {
                    $deliveryStatus = $dtoStatus->deliveryStatus ?? $dtoStatus->consignment['status'] ?? $deliveryStatus;
                }

                $order->update([
                    'courier_name' => 'steadfast',
                    'tracking_number' => $result['consignment']['tracking_code'] ?? null,
                    'consignment_id' => $result['consignment']['consignment_id'] ?? null,
                    'is_courier' => true,
                    'delivery_status' => $deliveryStatus,
                ]);

                Cache::flush();

                $responses[] = [
                    'order_id' => $orderId,
                    'success' => true,
                    'data' => $result
                ];
            } catch (CourierServiceException $e) {
                Log::error('CourierServiceException in bulk order', [
                    'order_id' => $orderId,
                    'message' => $e->getMessage()
                ]);
                $errors[] = [
                    'order_id' => $orderId,
                    'message' => $e->getMessage()
                ];
            } catch (\Exception $e) {
                Log::error('Exception in bulk order', [
                    'order_id' => $orderId,
                    'message' => $e->getMessage()
                ]);
                $errors[] = [
                    'order_id' => $orderId,
                    'message' => 'Unexpected error: ' . $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => count($errors) === 0,
            'processed' => $responses,
            'errors' => $errors
        ]);
    }



    public function getStatusByConsignmentId(string $consignmentId): JsonResponse
    {
        try {
            $result = $this->courierManager->getStatusByConsignmentId($consignmentId);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (CourierServiceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }

    public function getStatusByInvoice(string $invoice): JsonResponse
    {
        try {
            $result = $this->courierManager->getStatusByInvoice($invoice);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (CourierServiceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }

    public function getStatusByTrackingCode(string $trackingCode): JsonResponse
    {
        try {
            $result = $this->courierManager->getStatusByTrackingCode($trackingCode);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (CourierServiceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }
}
