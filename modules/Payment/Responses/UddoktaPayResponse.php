<?php

namespace Modules\Payment\Responses;

use Modules\Order\Entities\Order;
use Modules\Payment\GatewayResponse;
use Modules\Payment\HasTransactionReference;
use Modules\Payment\ShouldRedirect;

class UddoktaPayResponse extends GatewayResponse implements HasTransactionReference, ShouldRedirect
{
    private $order;
    private $clientResponse;

    public function __construct(Order $order, $clientResponse)
    {
        $this->order = $order;
        $this->clientResponse = $clientResponse;
    }

    public function getOrderId()
    {
        return $this->order->id;
    }

    public function getTransactionReference()
    {
        return $this->clientResponse['invoice_id'] ?? 'ref' . time();
    }

    public function getRedirectUrl()
    {
        return $this->clientResponse['payment_url'] ?? null;
    }

    public function isSuccessful()
    {
        return isset($this->clientResponse['status']) && $this->clientResponse['status'] === true;
    }

    public function isPending()
    {
        return false; // UddoktaPay doesn't have a pending status in the create response
    }

    public function isFailed()
    {
        return isset($this->clientResponse['status']) && $this->clientResponse['status'] === false;
    }

    public function getMessage()
    {
        return $this->clientResponse['message'] ?? '';
    }

    public function toArray()
    {
        return parent::toArray() + [
            'redirectUrl' => $this->getRedirectUrl(),
            'transactionReference' => $this->getTransactionReference(),
            'status' => $this->clientResponse['status'] ?? 'UNKNOWN',
            'message' => $this->getMessage(),
        ];
    }
}
