<?php

namespace App\Http\Requests\Patient;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'phone' => ['required', 'string', 'max:15'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'The full name field is required.',
            'full_name.max' => 'The full name field must not exceed 255 characters.',

            'gender.required' => 'The gender field is required.',
            'gender.in' => 'The selected gender is invalid.',

            'date_of_birth.required' => 'The date of birth field is required.',
            'date_of_birth.date' => 'The date of birth is invalid.',
            'date_of_birth.before_or_equal' => 'The date of birth must not be in the future.',

            'phone.required' => 'The phone number field is required.',
            'phone.max' => 'The phone number must not exceed 15 characters.',
            'phone.unique' => 'The phone number has already been taken.',

            'email.email' => 'The email address must be a valid email address.',
            'email.max' => 'The email address must not exceed 255 characters.',

            'address.max' => 'The address must not exceed 255 characters.',
        ];
    }
}