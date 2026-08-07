<?php

namespace App\Http\Requests\Appointment;

use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAppointmentRequest extends FormRequest
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
            'doctor_id' => ['nullable', 'integer', Rule::exists(Doctor::class, 'id')],
            'patient_id' => ['nullable', 'integer', Rule::exists(Patient::class, 'id')],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in(['scheduled', 'confirmed', 'cancelled', 'completed'])],
            'per_page' => ['nullable', 'integer', 'max:100', 'min:1']
        ];
    }

    public function messages(): array
    {
        return [
            'doctor_id.integer' => 'The selected doctor is invalid.',
            'doctor_id.exists' => 'The selected doctor does not exist.',

            'patient_id.integer' => 'The selected patient is invalid.',
            'patient_id.exists' => 'The selected patient does not exist.',

            'date.date_format' => 'The date must match format Y-m-d.',

            'status.in' => 'The selected status is invalid.',

            'per_page.integer' => 'The per page field must be an integer.',
            'per_page.max' => 'The per page field must not exceed 100.',
            'per_page.min' => 'The per page field must be at least 1.',
        ];
    }

    public function attributes(): array
    {
        return [
            'doctor_id' => 'Doctor',
            'patient_id' => 'Patient',
            'date' => 'Date',
            'status' => 'Status',
            'per_page' => 'Per Page',
        ];
    }
}
