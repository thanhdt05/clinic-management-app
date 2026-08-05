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
            'specialty_id.integer' => 'Specialty ID phải là số nguyên.',
            'specialty_id.exists' => 'Specialty ID không tồn tại.',

            'license_number.string' => 'Số giấy phép không hợp lệ.',
            'license_number.max' => 'Số giấy phép không được vượt quá 255 ký tự.',
            'license_number.unique' => 'Số giấy phép đã tồn tại.',

            'bio.string' => 'Thông tin không hợp lệ.',
        ];
    }

    public function attributes(): array
    {
        return [
            'specialty_id' => 'Chuyên khoa',
            'license_number' => 'Số giấy phép hành nghề',
            'bio' => 'Thông tin',
        ];
    }
}
