<?php

namespace App\Constants;

final class ActivityAction
{
    public const USER_CREATED = 'user.created';

    public const USER_UPDATED = 'user.updated';

    public const USER_DEACTIVATED = 'user.deactivated';

    public const USER_STATUS_CHANGED = 'user.status_changed';

    public const PATIENT_CREATED = 'patient.created';

    public const PATIENT_UPDATED = 'patient.updated';

    public const PATIENT_DELETED = 'patient.deleted';

    public const DOCTOR_CREATED = 'doctor.created';

    public const DOCTOR_UPDATED = 'doctor.updated';

    public const DOCTOR_DELETED = 'doctor.deleted';

    public const SPECIALTY_CREATED = 'specialty.created';

    public const SPECIALTY_UPDATED = 'specialty.updated';

    public const SPECIALTY_DELETED = 'specialty.deleted';

    public const APPOINTMENT_CREATED = 'appointment.created';

    public const APPOINTMENT_UPDATED = 'appointment.updated';

    public const APPOINTMENT_STATUS_CHANGED = 'appointment.status_changed';

    public const EXAMINATION_CREATED = 'examination.created';

    public const EXAMINATION_UPDATED = 'examination.updated';

    public const PRESCRIPTION_CREATED = 'prescription.created';

    public const PRESCRIPTION_UPDATED = 'prescription.updated';

    public const PRESCRIPTION_ITEM_ADDED = 'prescription.item_added';

    public const PRESCRIPTION_ITEM_UPDATED = 'prescription.item_updated';

    public const PRESCRIPTION_ITEM_REMOVED = 'prescription.item_removed';

    public const MEDICINE_CREATED = 'medicine.created';

    public const MEDICINE_UPDATED = 'medicine.updated';

    public const MEDICINE_DELETED = 'medicine.deleted';

    public const MEDICINE_STOCK_CHANGED = 'medicine.stock_changed';

    public const INVOICE_CREATED = 'invoice.created';

    public const INVOICE_UPDATED = 'invoice.updated';

    public const INVOICE_STATUS_CHANGED = 'invoice.status_changed';

    public const PAYMENT_CREATED = 'payment.created';

    public const PAYMENT_COMPLETED = 'payment.completed';

    public const PAYMENT_FAILED = 'payment.failed';
}
