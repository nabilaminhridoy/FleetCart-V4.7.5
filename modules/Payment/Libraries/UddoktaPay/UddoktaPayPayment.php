<?php

namespace Modules\Payment\Libraries\UddoktaPay;

use Exception;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Libraries\UddoktaPay\Exceptions\UddoktaPayException;

class UddoktaPayPayment
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Create a payment charge
     * API Endpoint: POST /api/checkout-v2 (for sandbox) or /api/checkout (for live)
     */
    public function create($amount, $invoiceId, $customerInfo, $billingAddress = [])
    {
        try {
            // Validate required configuration
            if (empty($this->config['api_key'])) {
                throw new UddoktaPayException('API Key is required');
            }

            if (empty($this->config['redirect_url'])) {
                throw new UddoktaPayException('Redirect URL is required');
            }

            if (empty($this->config['cancel_url'])) {
                throw new UddoktaPayException('Cancel URL is required');
            }

            // Validate customer information
            if (empty($customerInfo['name'])) {
                throw new UddoktaPayException('Customer name is required');
            }

            if (empty($customerInfo['email'])) {
                throw new UddoktaPayException('Customer email is required');
            }

            if (empty($customerInfo['phone'])) {
                throw new UddoktaPayException('Customer phone is required');
            }

            // Use the provided API URL
            $apiUrl = $this->config['api_url'];

            // Create payload according to UddoktaPay API documentation
            $payload = [
                'full_name' => $customerInfo['name'],
                'email' => $customerInfo['email'],
                'amount' => (string)round($amount),
                'metadata' => [
                    'order_id' => $invoiceId,
                    'phone' => $customerInfo['phone'],
                ],
                'redirect_url' => $this->config['redirect_url'],
                'return_type' => $this->config['return_type'] ?? 'GET',
                'cancel_url' => $this->config['cancel_url'],
            ];

            // Add webhook URL if provided
            if (!empty($this->config['webhook_url'])) {
                $payload['webhook_url'] = $this->config['webhook_url'];
            }

            // Add billing address to metadata if provided
            if (!empty($billingAddress)) {
                $payload['metadata']['billing_address'] = [
                    'address' => $billingAddress['address'] ?? '',
                    'city' => $billingAddress['city'] ?? '',
                    'state' => $billingAddress['state'] ?? '',
                    'postcode' => $billingAddress['postcode'] ?? '',
                    'country' => $billingAddress['country'] ?? '',
                ];
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'RT-UDDOKTAPAY-API-KEY' => $this->config['api_key'],
            ])->post($apiUrl, $payload);

            // Debug: Log the request and response
            \Log::info('UddoktaPay Create Charge Request:', [
                'url' => $apiUrl,
                'payload' => $payload,
                'response_status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            if (!$response->successful()) {
                throw new UddoktaPayException('Failed to create payment: ' . $response->body());
            }

            $data = $response->json();

            // Check if the response contains the payment_url
            if (!isset($data['payment_url']) || $data['status'] !== true) {
                 throw new UddoktaPayException($data['message'] ?? 'Payment creation failed, no payment URL received.');
            }

            return $data;

        } catch (Exception $e) {
            throw new UddoktaPayException('Payment creation error: ' . $e->getMessage());
        }
    }

    /**
     * Verify a payment
     * API Endpoint: POST /api/verify-payment
     */
    public function verify($invoiceId)
    {
        try {
            // Use the provided verify URL
            $apiUrl = $this->config['verify_url'];

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'RT-UDDOKTAPAY-API-KEY' => $this->config['api_key'],
            ])->post($apiUrl, ['invoice_id' => $invoiceId]);

            // Debug: Log the request and response
            \Log::info('UddoktaPay Verify Payment Request:', [
                'url' => $apiUrl,
                'invoice_id' => $invoiceId,
                'response_status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            if (!$response->successful()) {
                throw new UddoktaPayException('Failed to verify payment: ' . $response->body());
            }

            $data = $response->json();

            if (isset($data['status']) && $data['status'] === 'ERROR') {
                throw new UddoktaPayException($data['message'] ?? 'Payment verification failed');
            }

            return $data;

        } catch (Exception $e) {
            throw new UddoktaPayException('Payment verification error: ' . $e->getMessage());
        }
    }

    /**
     * Process webhook
     */
    public function processWebhook($payload)
    {
        try {
            // Validate webhook payload
            if (!isset($payload['status']) || !isset($payload['invoice_id'])) {
                throw new UddoktaPayException('Invalid webhook payload: Missing required fields.');
            }

            return $payload;

        } catch (Exception $e) {
            throw new UddoktaPayException('Webhook processing error: ' . $e->getMessage());
        }
    }

    /**
     * Refund a payment
     * API Endpoint: POST /api/refund-payment
     */
    public function refund($transactionId, $paymentMethod, $amount, $productName, $reason)
    {
        try {
            // Use the provided refund URL
            $apiUrl = $this->config['refund_url'];

            $payload = [
                'transaction_id' => $transactionId,
                'payment_method' => $paymentMethod,
                'amount' => (string)round($amount),
                'product_name' => $productName,
                'reason' => $reason,
            ];

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'RT-UDDOKTAPAY-API-KEY' => $this->config['api_key'],
            ])->post($apiUrl, $payload);

            if (!$response->successful()) {
                throw new UddoktaPayException('Failed to process refund: ' . $response->body());
            }

            $data = $response->json();

            if ($data['status'] !== true) {
                throw new UddoktaPayException($data['message'] ?? 'Refund failed');
            }

            return $data;

        } catch (Exception $e) {
            throw new UddoktaPayException('Refund processing error: ' . $e->getMessage());
        }
    }
}
