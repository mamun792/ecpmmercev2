<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payment\PayStationService;
use App\Services\Order\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class PaymentController extends Controller
{
    protected PayStationService $payStationService;
    protected OrderService $orderService;

    public function __construct(
        PayStationService $payStationService,
        OrderService $orderService
    ) {
        $this->payStationService = $payStationService;
        $this->orderService = $orderService;
    }

    /**
     * Initiate payment with PayStation
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initiate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'customer_email' => 'nullable|email|max:255',
                'customer_address' => 'nullable|string',
                'amount' => 'required|numeric|min:1',
                'items' => 'nullable|array',
                'reference' => 'nullable|string',
                'user_id' => 'nullable|integer',
                'session_id' => 'nullable|string',
                'shipping_cost' => 'nullable|numeric',
                'area' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $invoiceNumber = $this->payStationService->generateInvoiceNumber();

            // Complete payment data for order creation
            $paymentData = [
                'invoice_number' => $invoiceNumber,
                'user_id' => $request->user_id,
                'session_id' => $request->session_id,
                'customer_email' => $request->customer_email,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'shipping_address' => $request->customer_address,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'area' => $request->area,
                'payment_method' => 'paystation',
                'items' => $request->items ?? [],
                'amount' => $request->amount,
            ];

            // PayStation API payload
            $orderData = [
                'invoice_number' => $invoiceNumber,
                'amount' => $request->amount,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'customer_address' => $request->customer_address,
                'items' => $request->items ?? [],
                'reference' => $request->reference ?? 'Order Payment',
            ];

            // Store in cache for 1 hour (payment should complete within this time)
            cache()->put('payment_data_' . $invoiceNumber, $paymentData, now()->addHour());

            // Pass both PayStation payload and complete payment data
            $result = $this->payStationService->initiatePayment($orderData, $paymentData);

            return response()->json([
                'success' => true,
                'payment_url' => $result['payment_url'],
                'invoice_number' => $result['invoice_number'],
                'message' => $result['message'],
            ]);
        } catch (Exception $e) {
            Log::error('Payment Initiation Error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify transaction status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        Log::info('PAYMENT VERIFY ENDPOINT HIT', [
            'request_all' => $request->all(),
            'headers' => $request->headers->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()
        ]);


        try {
            $validator = Validator::make($request->all(), [
                'trx_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction ID is required',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $result = $this->payStationService->checkTransactionStatus($request->trx_id);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => $result['message'],
            ]);
        } catch (Exception $e) {
            Log::error('Transaction Verification Error', [
                'error' => $e->getMessage(),
                'trx_id' => $request->trx_id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build order payload from payment data
     *
     * @param array $paymentData
     * @param string $invoiceNumber
     * @param string|null $trxId
     * @return array
     */
    private function buildOrderPayload(array $paymentData, string $invoiceNumber, ?string $trxId = null): array
    {
        return [
            'user_id' => $paymentData['user_id'],
            'session_id' => $paymentData['session_id'],
            'customer_email' => $paymentData['customer_email'],
            'customer_name' => $paymentData['customer_name'],
            'customer_phone' => $paymentData['customer_phone'],
            'shipping_address' => $paymentData['shipping_address'],
            'shipping_cost' => $paymentData['shipping_cost'],
            'area' => $paymentData['area'],
            'payment_method' => 'paystation',
            'payment_status' => 'paid',
            'items' => $paymentData['items'],
            'paystation_invoice' => $invoiceNumber,
            'paystation_trx_id' => $trxId,
        ];
    }

    /**
     * Handle PayStation callback
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function callback(Request $request): JsonResponse
    {
        try {
            Log::info('PayStation Callback Received', ['data' => $request->all()]);

            $invoiceNumber = $request->invoice_number ?? $request->input('invoice_number');
            $trxId = $request->trx_id ?? $request->input('trx_id');
            $status = $request->status ?? $request->input('status');

            // Validate required parameters
            if (!$invoiceNumber || !$status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters: invoice_number and status',
                ], 400);
            }

            $normalizedStatus = $this->payStationService->normalizeStatus($status);

            Log::info('Processing payment callback', [
                'invoice_number' => $invoiceNumber,
                'status' => $normalizedStatus,
                'trx_id' => $trxId
            ]);

            // Update or create transaction history
            $this->payStationService->createOrUpdateFromUrlParams([
                'invoice_number' => $invoiceNumber,
                'status' => $status,
                'trx_id' => $trxId,
            ]);

            // Only create order if payment is successful
            if ($normalizedStatus === 'success') {
                // Check if order already exists for this invoice
                $existingTransaction = \App\Models\TransactionHistory::where('invoice_number', $invoiceNumber)
                    ->whereNotNull('order_id')
                    ->first();

                if ($existingTransaction && $existingTransaction->order_id) {
                    // Order already exists, return existing order data
                    $existingOrder = \App\Models\Order::find($existingTransaction->order_id);
                    
                    if ($existingOrder) {
                        Log::info('Order already exists for this payment', [
                            'invoice_number' => $invoiceNumber,
                            'order_number' => $existingOrder->order_number,
                        ]);

                        // Get complete transaction details from PayStation API
                        $transactionDetails = null;
                        if ($trxId) {
                            try {
                                $transactionDetails = $this->payStationService->checkTransactionStatus($trxId);
                            } catch (Exception $e) {
                                Log::warning('Failed to fetch transaction details from PayStation', [
                                    'trx_id' => $trxId,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }

                        return response()->json([
                            'success' => true,
                            'message' => 'Order already created',
                            'order_number' => $existingOrder->order_number,
                            'order_id' => $existingOrder->id,
                            'status' => $normalizedStatus,
                            'transaction' => $transactionDetails,
                        ]);
                    }
                }

                // Try to get payment data from cache first
                $paymentData = $this->payStationService->getPaymentData($invoiceNumber);

                // If not in cache, try to get from transaction history
                if (!$paymentData) {
                    Log::info('Payment data not in cache, retrieving from transaction history', [
                        'invoice_number' => $invoiceNumber
                    ]);

                    $transaction = \App\Models\TransactionHistory::where('invoice_number', $invoiceNumber)->first();
                    
                    if ($transaction && isset($transaction->gateway_response['order_payload'])) {
                        $paymentData = $transaction->gateway_response['order_payload'];
                        Log::info('Payment data retrieved from transaction history', [
                            'invoice_number' => $invoiceNumber
                        ]);
                    }
                }

                if ($paymentData) {
                    try {
                        // Build and create order
                        $orderPayload = $this->buildOrderPayload($paymentData, $invoiceNumber, $trxId);
                        $order = $this->orderService->createOrder($orderPayload);

                        // Get complete transaction details from PayStation API
                        $transactionDetails = null;
                        if ($trxId) {
                            try {
                                $transactionDetails = $this->payStationService->checkTransactionStatus($trxId);
                            } catch (Exception $e) {
                                Log::warning('Failed to fetch transaction details from PayStation', [
                                    'trx_id' => $trxId,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }

                        // Link transaction to order
                        $this->payStationService->updateTransactionHistory(
                            $invoiceNumber,
                            'success',
                            [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                            ]
                        );

                        // Clear cached payment data
                        $this->payStationService->clearPaymentData($invoiceNumber);

                        Log::info('Order created successfully from payment', [
                            'order_number' => $order->order_number,
                            'invoice_number' => $invoiceNumber,
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Order created successfully',
                            'order_number' => $order->order_number,
                            'order_id' => $order->id,
                            'status' => $normalizedStatus,
                            'transaction' => $transactionDetails, // Include complete transaction data
                        ]);
                    } catch (Exception $e) {
                        Log::error('Failed to create order from payment', [
                            'invoice_number' => $invoiceNumber,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'Payment successful but order creation failed: ' . $e->getMessage(),
                        ], 500);
                    }
                } else {
                    Log::warning('Payment successful but no cached payment data found', [
                        'invoice_number' => $invoiceNumber
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Payment data not found. Please contact support.',
                    ], 404);
                }
            }

            // For non-success statuses, just update transaction history
            return response()->json([
                'success' => true,
                'message' => 'Transaction status updated',
                'status' => $normalizedStatus,
            ]);
        } catch (Exception $e) {
            Log::error('Payment Callback Error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Callback processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
