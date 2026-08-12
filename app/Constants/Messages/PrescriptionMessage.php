<?php

namespace App\Constants\Messages;

final class PrescriptionMessage
{
    public const PRESCRIPTION_CREATED = 'Prescription created successfully.';

    public const PRESCRIPTION_UPDATED = 'Prescription updated successfully.';

    public const PRESCRIPTION_LIST_RETRIEVED = 'Prescription list retrieved successfully.';

    public const PRESCRIPTION_DETAILS_RETRIEVED = 'Prescription details retrieved successfully.';

    public const PRESCRIPTION_NOT_FOUND = 'Prescription not found.';

    public const PRESCRIPTION_ITEM_ADDED = 'Medicine item added to prescription successfully.';

    public const PRESCRIPTION_ITEM_UPDATED = 'Prescription item updated successfully.';

    public const PRESCRIPTION_ITEM_REMOVED = 'Prescription item removed successfully.';

    public const PRESCRIPTION_ITEM_NOT_FOUND = 'The prescription item does not exist.';

    public const EXAMINATION_ALREADY_HAS_PRESCRIPTION = 'This examination already has a prescription.';

    public const UNAUTHORIZED_EXAMINATION_PRESCRIPTION = 'You can only create prescriptions for your own examinations.';

    public const UNAUTHORIZED_PRESCRIPTION = 'You can only modify your own prescriptions.';
}
