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
            'name.required' => 'The name field is required.',
            'name.min' => 'The name field must be at least 5 characters.',
            'name.max' => 'The name field must not exceed 255 characters.',

            'email.required' => 'The email field is required.',
            'email.email' => 'The email field must be a valid email address.',
            'email.max' => 'The email field must not exceed 255 characters.',
            'email.unique' => 'The email has already been taken.',

            'password.required' => 'The password field is required.',
            'password.min' => 'The password field must be at least 8 characters.',
            'password.max' => 'The password field must not exceed 255 characters.',

            'password_confirm.required' => 'The password confirmation field is required.',
            'password_confirm.same' => 'The password confirmation does not match.',
            'password_confirm.max' => 'The password confirmation field must not exceed 255 characters.',

            'role_id.required' => 'The role field is required.',
            'role_id.integer' => 'The selected role is invalid.',
            'role_id.exists' => 'The selected role does not exist.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
            'role_id' => 'Role',
        ];
    }
}
