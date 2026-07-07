<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Gateways\StripeGateway;
use App\Services\Gateways\PaystackGateway;
use App\Services\Gateways\FlutterwaveGateway;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    public static function make(string $provider): PaymentGatewayInterface
    {
        return match ($provider) {
            'stripe' => app(StripeGateway::class),
            'paystack' => app(PaystackGateway::class), 
            'flutterwave' => app(FlutterwaveGateway::class), 
            default => throw new InvalidArgumentException("Unsupported gateway: {$provider}"),
        };
    }
}