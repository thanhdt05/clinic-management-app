<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:5', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'password_confirm' => ['required', 'string', 'same:password', 'max:255'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên không được để trống.',
            'name.min' => 'Tên phải có ít nhất 5 ký tự.',
            'name.max' => 'Tên không được vượt quá 255 ký tự.',

            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'email.unique' => 'Email đã tồn tại.',

            'password.required' => 'Password không được để trống.',
            'password.min' => 'Password phải có ít nhất 8 ký tự.',
            'password.max' => 'Password không được vượt quá 255 ký tự.',

            'password_confirm.required' => 'Password confirm không được để trống.',
            'password_confirm.same' => 'Password confirm không khớp.',
            'password_confirm.max' => 'Password confirm không được vượt quá 255 ký tự.',

            'role_id.required' => 'Vai trò không được để trống.',
            'role_id.integer' => 'Vai trò không hợp lệ.',
            'role_id.exists' => 'Vai trò không tồn tại.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên',
            'email' => 'Email',
            'password' => 'Password',
            'role_id' => 'Vai trò',
        ];
    }
}
