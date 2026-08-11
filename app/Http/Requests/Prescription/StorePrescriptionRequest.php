<?php

namespace App\Http\Requests\Prescription;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrescriptionRequest extends FormRequest
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
            'examination_id' => ['required', 'integer', 'exists:examinations,id', 'unique:prescriptions,examination_id'],
            'notes' => ['nullable', 'string'],

            'items' => ['sometimes', 'array'],
            'items.*.medicine_id' => ['required', 'integer', 'distinct', Rule::exists('medicines', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.dosage' => ['required', 'string', 'max:255'],
            'items.*.usage_instruction' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'examination_id.required' => 'Examination is required',
            'examination_id.integer' => 'Examination must be an integer',
            'examination_id.exists' => 'Examination not found',
            'examination_id.unique' => 'Examination already has a prescription',

            'notes.string' => 'Notes must be a string',

            'items.array' => 'Items must be an array',

            'items.*.medicine_id.required' => 'Medicine is required',
            'items.*.medicine_id.integer' => 'Medicine must be an integer',
            'items.*.medicine_id.distinct' => 'Medicine already added to prescription',
            'items.*.medicine_id.exists' => 'Medicine not found',

            'items.*.quantity.required' => 'Quantity is required',
            'items.*.quantity.integer' => 'Quantity must be an integer',
            'items.*.quantity.min' => 'Quantity must be at least 1',

            'items.*.dosage.required' => 'Dosage is required',
            'items.*.dosage.string' => 'Dosage must be a string',
            'items.*.dosage.max' => 'Dosage must be at most 255 characters',

            'items.*.usage_instruction.string' => 'Usage instruction must be a string',
            'items.*.usage_instruction.max' => 'Usage instruction must be at most 255 characters',
        ];
    }

    public function attributes(): array
    {
        return [
            'examination_id' => 'Examination',
            'notes' => 'Notes',
            'items' => 'Items',
            'items.*.medicine_id' => 'Medicine',
            'items.*.quantity' => 'Quantity',
            'items.*.dosage' => 'Dosage',
            'items.*.usage_instruction' => 'Usage instruction',
        ];
    }
}
