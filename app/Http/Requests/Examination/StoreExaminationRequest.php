<?php

namespace App\Http\Requests\Examination;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExaminationRequest extends FormRequest
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
            'appointment_id' => [
                'required',
                'integer',
                Rule::exists(Appointment::class, 'id'),
                Rule::unique('examinations', 'appointment_id'),
            ],
            'diagnosis' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_id.required' => 'The appointment field is required.',
            'appointment_id.exists' => 'The selected appointment does not exist.',
            'appointment_id.unique' => 'The selected appointment has already been examined.',
            'diagnosis.required' => 'The diagnosis field is required.',
            'notes.required' => 'The notes field is required.',
        ];
    }

    public function attributes(): array
    {
        return [
            'appointment_id' => 'Appointment',
            'diagnosis' => 'Diagnosis',
            'notes' => 'Notes',
        ];
    }
}
