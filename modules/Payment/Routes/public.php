<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\BkashPaymentController;
use Modules\Payment\Http\Controllers\UddoktaPayController;

Route::post('/bkash/get-token', [BkashPaymentController::class, 'getToken'])
    ->name('bkash.get_token');

Route::get('/bkash/create-payment', [BkashPaymentController::class, 'createPayment'])
    ->name('bkash.create_payment');

Route::post('/bkash/execute-payment', [BkashPaymentController::class, 'executePayment'])
    ->name('bkash.execute_payment');

Route::get('/bkash/query-payment', [BkashPaymentController::class, 'queryPayment'])
    ->name('bkash.query_payment');

// UddoktaPay Routes
Route::post('/uddoktapay/webhook', [UddoktaPayController::class, 'webhook'])
    ->name('payment.uddoktapay.webhook');

Route::get('/uddoktapay/verify', [UddoktaPayController::class, 'verify'])
    ->name('payment.uddoktapay.verify');
