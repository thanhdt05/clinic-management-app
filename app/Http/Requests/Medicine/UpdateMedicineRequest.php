<?php

namespace App\Http\Requests\Medicine;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicineRequest extends FormRequest
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
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('medicines', 'code')->ignore($this->route('medicine'))],
            'name' => ['sometimes', 'string', 'max:255'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.string' => 'The code must be a string.',
            'code.max' => 'The code must be at most 50 characters.',
            'code.unique' => 'The code already exists.',

            'name.string' => 'The name must be a string.',
            'name.max' => 'The name must be at most 255 characters.',

            'unit.string' => 'The unit must be a string.',
            'unit.max' => 'The unit must be at most 50 characters.',

            'price.numeric' => 'The price must be a number.',
            'price.min' => 'The price must be at least 0.',

            'is_active.boolean' => 'The is active must be a boolean.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Code',
            'name' => 'Name',
            'unit' => 'Unit',
            'price' => 'Price',
            'is_active' => 'Is active',
        ];
    }
}
