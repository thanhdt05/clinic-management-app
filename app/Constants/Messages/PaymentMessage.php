<?php

namespace App\Constants\Messages;

final class PaymentMessage
{
    public const PAYMENT_LIST_RETRIEVED = 'Payments retrieved successfully.';

    public const PAYMENT_CREATED = 'Payment created successfully.';

    public const PAYMENT_CAPTURED = 'Payment captured successfully.';

    public const INVOICE_MUST_BE_UNPAID_CREATE = 'Invoice must be unpaid to create a payment.';

    public const INVOICE_MUST_BE_UNPAID_CAPTURE = 'Invoice must be unpaid to capture payment.';

    public const AMOUNT_EXCEEDS_REMAINING = 'Payment amount exceeds the remaining invoice balance.';

    public const FAILED_TO_CREATE_PAYPAL_ORDER = 'Failed to create PayPal payment order. Please verify PayPal configuration and credentials.';

    public const FAILED_TO_CAPTURE_PAYPAL_PAYMENT = 'Failed to capture PayPal payment. Please try again later or use a different payment method.';

    public const ONLY_PENDING_CAN_BE_CAPTURED = 'Only pending payments can be captured.';

    public const MISSING_PAYPAL_ORDER_ID = 'Payment does not have a valid PayPal order ID.';
}
