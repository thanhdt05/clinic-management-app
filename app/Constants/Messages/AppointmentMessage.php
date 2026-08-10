<?php

namespace App\Constants\Messages;

final class AppointmentMessage
{
    public const APPOINTMENT_CREATED = 'Appointment created successfully.';

    public const APPOINTMENT_UPDATED = 'Appointment updated successfully.';

    public const APPOINTMENT_STATUS_UPDATED = 'Appointment status updated successfully.';

    public const APPOINTMENT_LIST_RETRIEVED = 'Appointment list retrieved successfully.';

    public const APPOINTMENT_DETAILS_RETRIEVED = 'Appointment details retrieved successfully.';

    public const APPOINTMENT_NOT_FOUND = 'Appointment not found.';

    public const CANNOT_UPDATE_NON_SCHEDULED = 'Appointment has already been confirmed, cancelled, or completed.';

    public const INVALID_STATUS_TRANSITION = 'Invalid status transition for this appointment.';
}
