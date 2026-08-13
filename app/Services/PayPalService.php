<?php

namespace App\Services;

use RuntimeException;
use Srmklive\PayPal\Exceptions\PayPalApiException;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalService
{
    public function createOrder(float $amount)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));

        $provider->getAccessToken();

        $order = $provider->createOrder([
            'intent' => 'CAPTURE',

            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => config('paypal.currency'),
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ],
            ],
        ]);

        $approvalUrl = collect($order['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        return [
            'order_id' => $order['id'] ?? null,
            'approval_url' => $approvalUrl,
        ];
    }

    public function captureOrder(string $orderId): array
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));

        $provider->getAccessToken();
        $provider->withExceptions();

        try {
            $response = $provider->capturePaymentOrder($orderId);

            $captureId = $provider->getCaptureIdFromOrder($response);
        } catch (PayPalApiException $e) {
            throw new RuntimeException(
                'PayPal capture failed.',
                previous: $e
            );
        }

        if (($response['status'] ?? null) !== 'COMPLETED' || empty($captureId)) {
            throw new RuntimeException(
                'PayPal payment was not completed.'
            );
        }

        return [
            'capture_id' => $captureId,
            'status' => $response['status'],
        ];
    }
}
