<?php

/**
 * Payment Gateways Configuration
 *
 * This file contains configuration for all payment gateways.
 * Each gateway can be enabled/disabled and configured with its
 * specific credentials and settings.
 *
 * To add a new gateway:
 * 1. Create a new gateway class in app/PaymentGateways
 * 2. Add the gateway configuration below
 * 3. Register the gateway in PaymentGatewayServiceProvider
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used
    | when no specific gateway is specified during payment processing.
    |
    */
    'default' => env('DEFAULT_PAYMENT_GATEWAY', 'credit_card'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the payment gateways for your application.
    | Each gateway can be enabled or disabled, and has its own configuration
    | options including API keys, secrets, and environment settings.
    |
    */
    'gateways' => [
        /*
        |--------------------------------------------------------------------------
        | Credit Card Gateway
        |--------------------------------------------------------------------------
        |
        | Generic credit card processing gateway. In production, this would
        | integrate with a payment processor like Stripe, Braintree, or
        | a bank's payment gateway API.
        |
        */
        'credit_card' => [
            'enabled' => env('GATEWAY_CREDIT_CARD_ENABLED', true),
            'merchant_id' => env('GATEWAY_CREDIT_CARD_MERCHANT_ID'),
            'api_key' => env('GATEWAY_CREDIT_CARD_API_KEY'),
            'sandbox' => env('GATEWAY_CREDIT_CARD_SANDBOX', true),
        ],

        /*
        |--------------------------------------------------------------------------
        | PayPal Gateway
        |--------------------------------------------------------------------------
        |
        | PayPal payment gateway configuration. Uses PayPal's REST API
        | for payment processing.
        |
        */
        'paypal' => [
            'enabled' => env('GATEWAY_PAYPAL_ENABLED', true),
            'client_id' => env('GATEWAY_PAYPAL_CLIENT_ID'),
            'client_secret' => env('GATEWAY_PAYPAL_CLIENT_SECRET'),
            'sandbox' => env('GATEWAY_PAYPAL_SANDBOX', true),
        ],

        /*
        |--------------------------------------------------------------------------
        | Stripe Gateway
        |--------------------------------------------------------------------------
        |
        | Stripe payment gateway configuration. Uses Stripe's API for
        | payment processing with support for various payment methods.
        |
        */
        'stripe' => [
            'enabled' => env('GATEWAY_STRIPE_ENABLED', true),
            'publishable_key' => env('GATEWAY_STRIPE_PUBLISHABLE_KEY'),
            'secret_key' => env('GATEWAY_STRIPE_SECRET_KEY'),
            'webhook_secret' => env('GATEWAY_STRIPE_WEBHOOK_SECRET'),
            'sandbox' => env('GATEWAY_STRIPE_SANDBOX', true),
        ],

        /*
        |--------------------------------------------------------------------------
        | Bank Transfer Gateway
        |--------------------------------------------------------------------------
        |
        | Bank transfer payment gateway. Generates reference numbers for
        | manual bank transfers.
        |
        */
        'bank_transfer' => [
            'enabled' => env('GATEWAY_BANK_TRANSFER_ENABLED', true),
            'bank_name' => env('GATEWAY_BANK_TRANSFER_BANK_NAME'),
            'account_number' => env('GATEWAY_BANK_TRANSFER_ACCOUNT_NUMBER'),
            'account_name' => env('GATEWAY_BANK_TRANSFER_ACCOUNT_NAME'),
            'sandbox' => env('GATEWAY_BANK_TRANSFER_SANDBOX', true),
        ],
    ],
];
