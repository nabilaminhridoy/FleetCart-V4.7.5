<?php

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Order\Entities\Order;
use Modules\Payment\Facades\Gateway;
use Modules\Payment\Libraries\UddoktaPay\UddoktaPayPayment;
use Modules\Transaction\Entities\Transaction;
use Modules\Admin\Http\Controllers\Controller;

class UddoktaPayController extends Controller
{
    /**
     * Handle UddoktaPay webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            // Get the API key from the request headers
            $headerApi = $request->header('RT-UDDOKTAPAY-API-KEY');
            
            // Determine if we're in sandbox mode
            $isSandbox = (bool)setting('uddoktapay_test_mode');
            
            // Get the appropriate API key based on mode
            $apiKey = $isSandbox 
                ? setting('uddoktapay_sandbox_api_key') 
                : setting('uddoktapay_live_api_key');
            
            // Verify the API key
            if ($headerApi !== $apiKey) {
                \Log::warning('UddoktaPay Webhook: Invalid API key', ['header_api' => $headerApi]);
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Action'], 401);
            }

            $payload = $request->all();
            
            // Validate webhook payload
            if (!isset($payload['status']) || !isset($payload['invoice_id'])) {
                \Log::warning('UddoktaPay Webhook: Invalid payload', ['payload' => $payload]);
                return response()->json(['status' => 'error', 'message' => 'Invalid webhook payload'], 400);
            }

            // Initialize UddoktaPay payment library
            $config = [
                'sandbox' => $isSandbox,
                'api_key' => $apiKey,
            ];

            $uddoktaPay = new UddoktaPayPayment($config);
            $webhookData = $uddoktaPay->processWebhook($payload);

            // Extract invoice ID and status
            $invoiceId = $webhookData['invoice_id'];
            $status = $webhookData['status'];

            // Find the order by invoice ID
            $orderId = $this->extractOrderIdFromInvoice($invoiceId);
            if (!$orderId) {
                \Log::error('UddoktaPay Webhook: Order ID could not be extracted from invoice', ['invoice_id' => $invoiceId]);
                return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
            }

            $order = Order::find($orderId);
            if (!$order) {
                \Log::error('UddoktaPay Webhook: Order not found', ['order_id' => $orderId]);
                return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
            }

            // Process payment based on status
            if ($status === 'COMPLETED') {
                $this->processSuccessfulPayment($order, $webhookData);
            } elseif (in_array($status, ['ERROR', 'FAILED', 'CANCELLED'])) {
                $this->processFailedPayment($order, $webhookData);
            } else {
                \Log::info('UddoktaPay Webhook: Received unhandled status', ['status' => $status, 'invoice_id' => $invoiceId]);
            }

            return response()->json(['status' => 'success', 'message' => 'Webhook processed successfully']);

        } catch (\Exception $e) {
            \Log::error('UddoktaPay webhook error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['status' => 'error', 'message' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Verify payment status
     */
    public function verify(Request $request): JsonResponse
    {
        try {
            $invoiceId = $request->get('invoice_id');
            
            if (!$invoiceId) {
                return response()->json(['status' => 'error', 'message' => 'Invoice ID required'], 400);
            }

            // Determine if we're in sandbox mode
            $isSandbox = (bool)setting('uddoktapay_test_mode');
            
            $config = [
                'sandbox' => $isSandbox,
                'api_key' => $isSandbox 
                    ? setting('uddoktapay_sandbox_api_key') 
                    : setting('uddoktapay_live_api_key'),
                'verify_url' => $isSandbox 
                    ? 'https://sandbox.uddoktapay.com/api/verify-payment' 
                    : (setting('uddoktapay_live_verify_url') ?? 'https://pay.uddoktapay.com/api/verify-payment'),
            ];

            $uddoktaPay = new UddoktaPayPayment($config);
            $verificationData = $uddoktaPay->verify($invoiceId);

            return response()->json([
                'status' => 'success',
                'data' => $verificationData
            ]);

        } catch (\Exception $e) {
            \Log::error('UddoktaPay verification error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Payment verification failed'], 500);
        }
    }

    /**
     * Extract order ID from invoice ID
     */
    private function extractOrderIdFromInvoice($invoiceId): ?int
    {
        // Invoice ID format: INV-{order_id}-{random_string}
        if (preg_match('/INV-(\d+)-/', $invoiceId, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }

    /**
     * Process successful payment
     */
    private function processSuccessfulPayment(Order $order, array $webhookData): void
    {
        // Create transaction record
        $transaction = Transaction::create([
            'order_id' => $order->id,
            'transaction_id' => $webhookData['transaction_id'] ?? $webhookData['invoice_id'],
            'payment_method' => 'uddoktapay',
            'amount' => $webhookData['amount'] ?? $order->total->amount(),
            'status' => 'completed',
            'payload' => json_encode($webhookData),
        ]);

        // Update order status
        $order->update(['status' => 'processing']);

        // Add order to session for checkout completion
        session(['placed_order' => $order]);
    }

    /**
     * Process failed payment
     */
    private function processFailedPayment(Order $order, array $webhookData): void
    {
        // Create transaction record
        $transaction = Transaction::create([
            'order_id' => $order->id,
            'transaction_id' => $webhookData['transaction_id'] ?? $webhookData['invoice_id'],
            'payment_method' => 'uddoktapay',
            'amount' => $webhookData['amount'] ?? $order->total->amount(),
            'status' => 'failed',
            'payload' => json_encode($webhookData),
        ]);

        // Update order status
        $order->update(['status' => 'payment_failed']);
    }
}
