<?php

namespace App\Http\Requests\Patient;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
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
            'full_name' => ['sometimes', 'string', 'max:255'],
            'gender' => ['sometimes', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['sometimes', 'date', 'before_or_equal:today'],
            'phone' => ['sometimes', 'string', 'max:15'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.sometimes' => 'The full name field is required when provided.',
            'full_name.max' => 'The full name field must not exceed 255 characters.',

            'gender.sometimes' => 'The gender field is required when provided.',
            'gender.in' => 'The selected gender is invalid.',

            'date_of_birth.sometimes' => 'The date of birth field is required when provided.',
            'date_of_birth.date' => 'The date of birth is invalid.',
            'date_of_birth.before_or_equal' => 'The date of birth must not be in the future.',

            'phone.sometimes' => 'The phone number field is required when provided.',
            'phone.max' => 'The phone number must not exceed 15 characters.',

            'email.email' => 'The email address must be a valid email address.',
            'email.max' => 'The email address must not exceed 255 characters.',

            'address.max' => 'The address must not exceed 255 characters.',
        ];
    }
}
