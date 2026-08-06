<?php

namespace App\Http\Requests\Specialty;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpecialtyRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', Rule::unique('specialties', 'name')],
            'description' => ['nullable', 'string']
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The specialty name field is required.',
            'name.unique' => 'The specialty name has already been taken.',
            'name.max' => 'The specialty name field must not exceed 255 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Specialty Name',
            'description' => 'Specialty Description',
        ];
    }
}
