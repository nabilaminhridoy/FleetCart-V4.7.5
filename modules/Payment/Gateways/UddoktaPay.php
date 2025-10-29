<?php

namespace Modules\Payment\Gateways;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Order\Entities\Order;
use Modules\Payment\GatewayInterface;
use Modules\Payment\Libraries\UddoktaPay\UddoktaPayPayment;
use Modules\Payment\Responses\UddoktaPayResponse;

class UddoktaPay implements GatewayInterface
{
    public $label;
    public $description;

    public function __construct()
    {
        $this->label = setting('uddoktapay_label');
        $this->description = setting('uddoktapay_description');
    }

    /**
     * @throws Exception
     */
    public function purchase(Order $order, Request $request)
    {
        // UddoktaPay supports BDT and other currencies, but we'll check if currency is supported
        $supported_currencies = ['BDT', 'USD', 'EUR', 'GBP'];
        
        if (!in_array(currency(), $supported_currencies)) {
            throw new Exception(trans('payment::messages.currency_not_supported'));
        }

        // Determine if we're in sandbox mode
        $isSandbox = (bool)setting('uddoktapay_test_mode');
        
        // Validate required settings based on mode
        if ($isSandbox) {
            if (empty(setting('uddoktapay_sandbox_api_key'))) {
                throw new Exception('UddoktaPay Sandbox API Key is required. Please configure it in settings.');
            }
            if (empty(setting('uddoktapay_sandbox_api_url'))) {
                throw new Exception('UddoktaPay Sandbox API URL is required. Please configure it in settings.');
            }
        } else {
            if (empty(setting('uddoktapay_live_api_key'))) {
                throw new Exception('UddoktaPay Live API Key is required. Please configure it in settings.');
            }
            if (empty(setting('uddoktapay_live_api_url'))) {
                throw new Exception('UddoktaPay Live API URL is required. Please configure it in settings.');
            }
        }

        $config = [
            'sandbox' => $isSandbox,
            'api_key' => $isSandbox 
                ? setting('uddoktapay_sandbox_api_key') 
                : setting('uddoktapay_live_api_key'),
            'api_url' => $isSandbox 
                ? setting('uddoktapay_sandbox_api_url') 
                : setting('uddoktapay_live_api_url'),
            'verify_url' => $isSandbox 
                ? 'https://sandbox.uddoktapay.com/api/verify-payment' 
                : (setting('uddoktapay_live_verify_url') ?? 'https://pay.uddoktapay.com/api/verify-payment'),
            'refund_url' => $isSandbox 
                ? 'https://sandbox.uddoktapay.com/api/refund-payment' 
                : (setting('uddoktapay_live_refund_url') ?? 'https://pay.uddoktapay.com/api/refund-payment'),
            'webhook_url' => $this->getWebhookUrl($order),
            'redirect_url' => $this->getRedirectUrl($order),
            'cancel_url' => $this->getPaymentFailedUrl($order),
            'return_type' => 'GET', // Using GET as return type
        ];

        $payment = new UddoktaPayPayment($config);

        $invoiceId = 'INV-' . $order->id . '-' . Str::substr(Str::uuid()->toString(), 0, 8);
        $amount = $order->total->convertToCurrentCurrency()->round()->amount();

        // Build customer information with validation
        $customerFirstName = trim($order->customer_first_name ?? '');
        $customerLastName = trim($order->customer_last_name ?? '');
        $customerName = trim($customerFirstName . ' ' . $customerLastName);
        
        if (empty($customerName)) {
            $customerName = 'Customer'; // Fallback name
        }

        $customerInfo = [
            'name' => $customerName,
            'email' => $order->customer_email ?? '',
            'phone' => $order->customer_phone ?? '',
        ];

        // Validate phone number (required for UddoktaPay)
        if (empty($customerInfo['phone'])) {
            throw new Exception('Customer phone number is required for UddoktaPay payment');
        }

        // Validate email (required for UddoktaPay)
        if (empty($customerInfo['email'])) {
            throw new Exception('Customer email is required for UddoktaPay payment');
        }

        $billingAddress = [
            'address' => trim(($order->billing_address_1 ?? '') . ' ' . ($order->billing_address_2 ?? '')),
            'city' => $order->billing_city ?? '',
            'state' => $order->billing_state ?? '',
            'postcode' => $order->billing_zip ?? '',
            'country' => $order->billing_country ?? 'Bangladesh',
        ];

        $response = $payment->create($amount, $invoiceId, $customerInfo, $billingAddress);

        return new UddoktaPayResponse($order, $response);
    }

    public function complete(Order $order)
    {
        return new UddoktaPayResponse($order, request()->all());
    }

    private function getRedirectUrl($order)
    {
        return route('checkout.complete.store', ['orderId' => $order->id, 'paymentMethod' => 'uddoktapay']);
    }

    private function getPaymentFailedUrl($order)
    {
        return route('checkout.payment_canceled.store', ['orderId' => $order->id, 'paymentMethod' => 'uddoktapay']);
    }

    private function getWebhookUrl($order)
    {
        return route('payment.uddoktapay.webhook');
    }
}
