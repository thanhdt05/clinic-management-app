<?php

namespace App\Constants\Messages;

final class ExaminationMessage
{
    public const EXAMINATION_CREATED = 'Examination recorded successfully.';

    public const EXAMINATION_UPDATED = 'Examination updated successfully.';

    public const EXAMINATION_LIST_RETRIEVED = 'Examinations fetched successfully.';

    public const EXAMINATION_DETAILS_RETRIEVED = 'Examination retrieved successfully.';

    public const EXAMINATION_NOT_FOUND = 'Examination not found.';

    public const UNAUTHORIZED_APPOINTMENT_EXAMINATION = 'You are not authorized to examine this appointment.';

    public const UNAUTHORIZED_EXAMINATION_ACCESS = 'You can only access your own examination.';
}
