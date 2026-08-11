<?php

namespace App\Constants\Messages;

final class PrescriptionMessage
{
    public const PRESCRIPTION_CREATED = 'Prescription created successfully.';

    public const PRESCRIPTION_UPDATED = 'Prescription updated successfully.';

    public const PRESCRIPTION_LIST_RETRIEVED = 'Prescription list retrieved successfully.';

    public const PRESCRIPTION_DETAILS_RETRIEVED = 'Prescription details retrieved successfully.';

    public const PRESCRIPTION_NOT_FOUND = 'Prescription not found.';

    public const EXAMINATION_ALREADY_HAS_PRESCRIPTION = 'This examination already has a prescription.';

    public const UNAUTHORIZED_EXAMINATION_PRESCRIPTION = 'You can only create prescriptions for your own examinations.';
}
