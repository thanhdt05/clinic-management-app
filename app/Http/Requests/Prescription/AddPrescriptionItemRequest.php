<?php

namespace App\Http\Requests\Prescription;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPrescriptionItemRequest extends FormRequest
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
        $prescription = $this->route('prescription');

        return [
            'medicine_id' => [
                'required', 'integer',
                Rule::exists('medicines', 'id')->whereNull('deleted_at'),
                Rule::unique('prescription_items', 'medicine_id')
                    ->where(function ($query) use ($prescription) {
                        return $query->where('prescription_id', $prescription->id);
                    }),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'dosage' => ['required', 'string', 'max:255'],
            'usage_instruction' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'medicine_id.required' => 'The medicine field is required.',
            'medicine_id.exists' => 'The medicine does not exist.',
            'medicine_id.unique' => 'The medicine already exists in the prescription.',

            'quantity.required' => 'The quantity field is required.',
            'quantity.integer' => 'The quantity must be an integer.',
            'quantity.min' => 'The quantity may not be less than 1.',

            'dosage.required' => 'The dosage field is required.',
            'dosage.max' => 'The dosage may not be greater than 255 characters.',

            'usage_instruction.required' => 'The usage instruction field is required.',
        ];
    }

    public function attributes()
    {
        return [
            'medicine_id' => 'Medicine',
            'quantity' => 'Quantity',
            'dosage' => 'Dosage',
            'usage_instruction' => 'Usage Instruction',
        ];
    }
}
