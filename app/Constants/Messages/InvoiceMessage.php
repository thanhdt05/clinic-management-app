<?php

namespace App\Constants\Messages;

final class InvoiceMessage
{
    public const INVOICE_CREATED = 'Invoice created successfully.';

    public const INVOICE_UPDATED = 'Invoice updated successfully.';

    public const INVOICE_STATUS_UPDATED = 'Invoice status updated successfully.';

    public const INVOICE_LIST_RETRIEVED = 'Invoice listed successfully.';

    public const INVOICE_DETAILS_RETRIEVED = 'Invoice fetched successfully.';

    public const EXAMINATION_ALREADY_HAS_INVOICE = 'The examination already has an invoice.';

    public const DISCOUNT_EXCEEDS_SUBTOTAL = 'Discount cannot exceed subtotal.';

    public const ONLY_UNPAID_CAN_BE_MODIFIED = 'Only unpaid invoices can be modified or cancelled.';

    public const INVOICE_HAS_COMPLETED_PAYMENTS = 'Invoice cannot be modified or cancelled because it has completed payments.';
}
