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
            'full_name.required' => 'Họ và tên là bắt buộc',
            'full_name.max' => 'Họ và tên không được vượt quá 255 ký tự',

            'gender.required' => 'Giới tính là bắt buộc',
            'gender.in' => 'Giới tính không hợp lệ',

            'date_of_birth.required' => 'Ngày sinh là bắt buộc',
            'date_of_birth.date' => 'Ngày sinh không hợp lệ',
            'date_of_birth.before_or_equal' => 'Ngày sinh không được lớn hơn ngày hiện tại',

            'phone.required' => 'Số điện thoại là bắt buộc',
            'phone.max' => 'Số điện thoại không được vượt quá 15 ký tự',
            'phone.unique' => 'Số điện thoại đã tồn tại',

            'email.email' => 'Email không hợp lệ',
            'email.max' => 'Email không được vượt quá 255 ký tự',

            'address.max' => 'Địa chỉ không được vượt quá 255 ký tự',
        ];
    }
}
