<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorRequest extends FormRequest
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
        $doctor = $this->route('doctor');

        return [
            'specialty_id' => ['sometimes', 'integer', 'exists:specialties,id'],
            'license_number' => ['sometimes', 'string', 'max:255', Rule::unique('doctors', 'license_number')->ignore($doctor)],
            'bio' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'specialty_id.integer' => 'The selected specialty is invalid.',
            'specialty_id.exists' => 'The selected specialty does not exist.',

            'license_number.string' => 'The license number field must be a string.',
            'license_number.max' => 'The license number field must not exceed 255 characters.',
            'license_number.unique' => 'The license number has already been taken.',

            'bio.string' => 'The bio field must be a string.',
        ];
    }

    public function attributes(): array
    {
        return [
            'specialty_id' => 'Specialty',
            'license_number' => 'License Number',
            'bio' => 'Bio',
        ];
    }
}
