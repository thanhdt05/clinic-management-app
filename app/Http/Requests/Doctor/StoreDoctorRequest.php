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
            'user_id.required' => 'User ID không được để trống.',
            'user_id.integer' => 'User ID phải là số nguyên.',
            'user_id.exists' => 'User ID không tồn tại.',
            'user_id.unique' => 'User ID đã tồn tại.',

            'specialty_id.required' => 'Specialty ID không được để trống.',
            'specialty_id.integer' => 'Specialty ID không hợp lệ.',
            'specialty_id.exists' => 'Specialty ID không tồn tại.',

            'license_number.required' => 'License number không được để trống.',
            'license_number.string' => 'License number không hợp lệ.',
            'license_number.max' => 'License number không được vượt quá 255 ký tự.',
            'license_number.unique' => 'License number đã tồn tại.',

            'bio.string' => 'Bio không hợp lệ.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'Người dùng',
            'specialty_id' => 'Chuyên khoa',
            'license_number' => 'Số giấy phép hành nghề',
            'bio' => 'Thông tin',
        ];
    }
}
