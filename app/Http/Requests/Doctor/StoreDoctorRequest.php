<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id', Rule::unique('doctors', 'user_id')],
            'specialty_id' => ['required', 'integer', 'exists:specialties,id'],
            'license_number' => ['required', 'string', 'max:255', Rule::unique('doctors', 'license_number')],
            'bio' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'The user field is required.',
            'user_id.integer' => 'The selected user is invalid.',
            'user_id.exists' => 'The selected user does not exist.',
            'user_id.unique' => 'The user has already been assigned as a doctor.',

            'specialty_id.required' => 'The specialty field is required.',
            'specialty_id.integer' => 'The selected specialty is invalid.',
            'specialty_id.exists' => 'The selected specialty does not exist.',

            'license_number.required' => 'The license number field is required.',
            'license_number.string' => 'The license number field must be a string.',
            'license_number.max' => 'The license number field must not exceed 255 characters.',
            'license_number.unique' => 'The license number has already been taken.',

            'bio.string' => 'The bio field must be a string.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'User',
            'specialty_id' => 'Specialty',
            'license_number' => 'License Number',
            'bio' => 'Bio',
        ];
    }
}
