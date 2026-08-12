<?php

namespace App\Http\Requests\Prescription;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionItemRequest extends FormRequest
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
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'dosage' => ['sometimes', 'string', 'max:255'],
            'usage_instruction' => ['sometimes', 'string'],
        ];
    }

    public function messages() {
        return [
            'quantity.required' => 'The quantity field is required.',
            'quantity.integer' => 'The quantity must be an integer.',
            'quantity.min' => 'The quantity may not be less than 1.',

            'dosage.required' => 'The dosage field is required.',
            'dosage.max' => 'The dosage may not be greater than 255 characters.',

            'usage_instruction.required' => 'The usage instruction field is required.',
        ];
    }

    public function attributes() {
        return [
            'quantity' => 'Quantity',
            'dosage' => 'Dosage',
            'usage_instruction' => 'Usage Instruction',
        ];
    }
}
