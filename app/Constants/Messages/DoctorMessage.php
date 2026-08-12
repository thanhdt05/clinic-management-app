<?php

namespace App\Constants\Messages;

final class DoctorMessage
{
    public const DOCTOR_CREATED = 'Doctor created successfully.';

    public const DOCTOR_UPDATED = 'Doctor updated successfully.';

    public const DOCTOR_DELETED = 'Doctor deleted successfully.';

    public const DOCTOR_LIST_RETRIEVED = 'Doctor list retrieved successfully.';

    public const DOCTOR_DETAILS_RETRIEVED = 'Doctor details retrieved successfully.';

    public const USER_NOT_DOCTOR = 'The selected user does not have the Doctor role.';

    public const DOCTOR_NOT_FOUND = 'Doctor not found.';
}
