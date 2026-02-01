<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TransactionHistory;
use Exception;

class PayStationService
{
    private string $merchantId;
    private string $password;
    private string $apiBaseUrl;
    private string $callbackUrl;

    public function __construct()
    {
        $this->merchantId = config('services.paystation.merchant_id');
        $this->password = config('services.paystation.password');
        $this->apiBaseUrl = config('services.paystation.api_url');
        $this->callbackUrl = config('services.paystation.callback_url');
    }

    /**
     * Initiate payment with PayStation
     *
     * @param array $orderData PayStation API payload
     * @param array|null $completePaymentData Complete payment data for order creation
     * @return array
     * @throws Exception
     */
    public function initiatePayment(array $orderData, ?array $completePaymentData = null): array
    {
        try {
            $payload = [
                'merchantId' => $this->merchantId,
                'password' => $this->password,
                'invoice_number' => $orderData['invoice_number'],
                'currency' => 'BDT',
                'payment_amount' => (int) $orderData['amount'],
                'pay_with_charge' => 0,
                'reference' => $orderData['reference'] ?? 'Order Payment',
                'cust_name' => $orderData['customer_name'],
                'cust_phone' => $orderData['customer_phone'],
                'cust_email' => $orderData['customer_email'] ?? '',
                'cust_address' => $orderData['customer_address'] ?? '',
                'callback_url' => $this->callbackUrl,
                'checkout_items' => json_encode($orderData['items'] ?? []),
                'opt_a' => $orderData['opt_a'] ?? null,
                'opt_b' => $orderData['opt_b'] ?? null,
                'opt_c' => $orderData['opt_c'] ?? null,
            ];

            Log::info('PayStation Payment Initiation Request', ['payload' => $payload]);

            $response = Http::timeout(30)
                ->post($this->apiBaseUrl . '/initiate-payment', $payload);

            $responseData = $response->json();

            Log::info('PayStation Payment Initiation Response', ['response' => $responseData]);

            // PayStation returns status_code as string "200", not integer
            if (!$response->successful() || 
                (isset($responseData['status_code']) && $responseData['status_code'] != 200) ||
                (isset($responseData['status']) && $responseData['status'] !== 'success')) {
                throw new Exception(
                    $responseData['message'] ?? 'Failed to initiate payment'
                );
            }

            // Store transaction history with complete order payload for tracking
            // Use complete payment data if provided, otherwise use orderData
            $payloadToStore = $completePaymentData ?? $orderData;
            
            $this->storeTransactionHistory([
                'transaction_id' => $responseData['invoice_number'],
                'invoice_number' => $responseData['invoice_number'],
                'amount' => $orderData['amount'],
                'customer_name' => $orderData['customer_name'],
                'customer_email' => $orderData['customer_email'],
                'customer_phone' => $orderData['customer_phone'],
                'status' => 'pending',
                'gateway_response' => [
                    'paystation_response' => $responseData,
                    'order_payload' => $payloadToStore, // Store complete order data for tracking
                ],
                'transaction_date' => now(),
            ]);

            return [
                'success' => true,
                'payment_url' => $responseData['payment_url'],
                'invoice_number' => $responseData['invoice_number'],
                'payment_amount' => $responseData['payment_amount'],
                'message' => $responseData['message'] ?? 'Payment link created successfully',
            ];
        } catch (Exception $e) {
            Log::error('PayStation Payment Initiation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception('Payment initiation failed: ' . $e->getMessage());
        }
    }

    /**
     * Check transaction status and update history
     *
     * @param string $trxId
     * @return array
     * @throws Exception
     */
    public function checkTransactionStatus(string $trxId): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'merchantId' => $this->merchantId,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiBaseUrl . '/v2/transaction-status', [
                    'trxId' => $trxId,
                ]);

            $responseData = $response->json();

            Log::info('PayStation Transaction Status Response', [
                'trxId' => $trxId,
                'response' => $responseData
            ]);

            // PayStation returns status_code as string "200", not integer
            if (!$response->successful() ||
                (isset($responseData['status_code']) && $responseData['status_code'] != 200) ||
                (isset($responseData['status']) && $responseData['status'] !== 'success')) {
                throw new Exception(
                    $responseData['message'] ?? 'Failed to check transaction status'
                );
            }

            $transactionData = $responseData['data'] ?? [];

            // Update transaction history with the latest status from PayStation
            if (!empty($transactionData)) {
                $normalizedStatus = $this->normalizeStatus($transactionData['trx_status'] ?? 'unknown');

                // Try to update existing transaction history
                $updateResult = $this->updateTransactionHistory(
                    $transactionData['invoice_number'] ?? $trxId,
                    $normalizedStatus,
                    [
                        'payment_method' => $transactionData['payment_method'] ?? null,
                        'gateway_response' => $transactionData,
                    ]
                );

                if (!$updateResult) {
                    Log::warning('Failed to update transaction history during status check', [
                        'trx_id' => $trxId,
                        'invoice_number' => $transactionData['invoice_number'] ?? 'unknown',
                        'status' => $normalizedStatus
                    ]);
                } else {
                    Log::info('Transaction history updated during status check', [
                        'trx_id' => $trxId,
                        'status' => $normalizedStatus
                    ]);
                }
            }

            return [
                'success' => true,
                'data' => $transactionData,
                'message' => $responseData['message'] ?? 'Transaction found',
            ];
        } catch (Exception $e) {
            Log::error('PayStation Transaction Status Error', [
                'trxId' => $trxId,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Transaction status check failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate unique invoice number
     *
     * @return string
     */
    public function generateInvoiceNumber(): string
    {
        return 'INV-' . time() . '-' . rand(1000, 9999);
    }

    /**
     * Verify payment callback data
     *
     * @param array $callbackData
     * @return bool
     */
    public function verifyCallback(array $callbackData): bool
    {
        // Add any signature verification logic here if PayStation provides it
        // For now, we'll verify by checking transaction status
        return isset($callbackData['invoice_number']) || isset($callbackData['trx_id']);
    }

    /**
     * Store transaction history
     *
     * @param array $data
     * @return TransactionHistory
     */
    private function storeTransactionHistory(array $data): TransactionHistory
    {
        $status = $data['status'] ?? 'pending';

        // Normalize status to match our enum values
        $normalizedStatus = $this->normalizeStatus($status);

        return TransactionHistory::create([
            'transaction_id' => $data['transaction_id'],
            'invoice_number' => $data['invoice_number'],
            'amount' => $data['amount'],
            'customer_name' => $data['customer_name'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'status' => $normalizedStatus,
            'payment_gateway' => 'paystation',
            'gateway_response' => $data['gateway_response'] ?? null,
            'transaction_date' => $data['transaction_date'] ?? now(),
        ]);
    }

    /**
     * Normalize status to match our enum values
     *
     * @param string $status
     * @return string
     */
    public function normalizeStatus(string $status): string
    {
        $statusMap = [
            'pending' => 'pending',
            'processing' => 'processing',
            'success' => 'success',
            'successful' => 'success', // PayStation returns "Successful"
            'completed' => 'success',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled', // PayStation sends "Canceled"
            'cancel' => 'cancelled',
        ];

        $normalized = strtolower($status);
        return $statusMap[$normalized] ?? 'pending';
    }

    /**
     * Create or update transaction history from URL parameters
     *
     * @param array $urlParams
     * @return TransactionHistory
     */
    public function createOrUpdateFromUrlParams(array $urlParams): TransactionHistory
    {
        $invoiceNumber = $urlParams['invoice_number'];
        $status = $this->normalizeStatus($urlParams['status'] ?? 'pending');
        $trxId = $urlParams['trx_id'] ?? null;

        // Try to find existing transaction
        $transaction = TransactionHistory::where('invoice_number', $invoiceNumber)->first();

        if ($transaction) {
            // Update existing transaction
            $updateData = ['status' => $status];

            if ($trxId) {
                $updateData['transaction_id'] = $trxId;
            }

            $transaction->update($updateData);

            Log::info('Transaction history updated from URL params', [
                'invoice_number' => $invoiceNumber,
                'status' => $status,
                'trx_id' => $trxId
            ]);

            return $transaction;
        }

        // Create new transaction from URL params
        $newTransaction = TransactionHistory::create([
            'transaction_id' => $trxId ?: $invoiceNumber,
            'invoice_number' => $invoiceNumber,
            'amount' => 0, // Will be updated when we get full payment data
            'status' => $status,
            'payment_gateway' => 'paystation',
            'gateway_response' => $urlParams,
            'transaction_date' => now(),
        ]);

        Log::info('Transaction history created from URL params', [
            'invoice_number' => $invoiceNumber,
            'status' => $status
        ]);

        return $newTransaction;
    }

    /**
     * Update transaction history status
     *
     * @param string $identifier Transaction ID or Invoice Number
     * @param string $status
     * @param array $additionalData
     * @return bool
     */
    public function updateTransactionHistory(string $identifier, string $status, array $additionalData = []): bool
    {
        // Try to find by transaction_id first, then by invoice_number
        $transaction = TransactionHistory::where('transaction_id', $identifier)
            ->orWhere('invoice_number', $identifier)
            ->first();

        if (!$transaction) {
            Log::warning('Transaction not found for update', ['identifier' => $identifier]);
            return false;
        }

        $updateData = ['status' => $status];

        if (isset($additionalData['payment_method'])) {
            $updateData['payment_method'] = $additionalData['payment_method'];
        }

        if (isset($additionalData['gateway_response'])) {
            $updateData['gateway_response'] = $additionalData['gateway_response'];
        }

        if (isset($additionalData['order_id'])) {
            $updateData['order_id'] = $additionalData['order_id'];
            $updateData['order_number'] = $additionalData['order_number'] ?? null;
        }

        return $transaction->update($updateData);
    }

    /**
     * Get cached payment data by invoice number
     *
     * @param string $invoiceNumber
     * @return array|null
     */
    public function getPaymentData(string $invoiceNumber): ?array
    {
        return cache()->get('payment_data_' . $invoiceNumber);
    }

    /**
     * Clear cached payment data
     *
     * @param string $invoiceNumber
     * @return void
     */
    public function clearPaymentData(string $invoiceNumber): void
    {
        cache()->forget('payment_data_' . $invoiceNumber);
    }
}
