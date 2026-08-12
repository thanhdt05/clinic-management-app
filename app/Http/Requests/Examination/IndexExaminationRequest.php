<?php

namespace App\Http\Requests\Examination;

use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexExaminationRequest extends FormRequest
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
            'per_page' => ['nullable', 'integer', 'max:100', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'doctor_id.exists' => 'Doctor not found.',
            'patient_id.exists' => 'Patient not found.',

            'per_page.integer' => 'Per page must be an integer.',
            'per_page.max' => 'Per page must not exceed 100.',
            'per_page.min' => 'Per page must be at least 1.',
        ];
    }

    public function attributes(): array
    {
        return [
            'doctor_id' => 'Doctor',
            'patient_id' => 'Patient',
            'per_page' => 'Per page',
        ];
    }
}
