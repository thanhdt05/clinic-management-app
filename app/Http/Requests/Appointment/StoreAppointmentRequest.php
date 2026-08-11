<?php

namespace App\Http\Requests\Appointment;

use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', Rule::exists(Patient::class, 'id')->whereNull('deleted_at')],
            'doctor_id' => ['required', 'integer', Rule::exists(Doctor::class, 'id')],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'reason' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'The patient field is required.',
            'patient_id.integer' => 'The selected patient is invalid.',
            'patient_id.exists' => 'The selected patient does not exist.',

            'doctor_id.required' => 'The doctor field is required.',
            'doctor_id.integer' => 'The selected doctor is invalid.',
            'doctor_id.exists' => 'The selected doctor does not exist.',

            'scheduled_at.required' => 'The scheduled time field is required.',
            'scheduled_at.date' => 'The scheduled time must be a valid date.',
            'scheduled_at.after' => 'The scheduled time must be after the current date and time.',

            'reason.string' => 'The reason field must be a string.',
        ];
    }

    public function attributes(): array
    {
        return [
            'patient_id' => 'Patient',
            'doctor_id' => 'Doctor',
            'scheduled_at' => 'Scheduled Time',
            'reason' => 'Reason',
        ];
    }
}
