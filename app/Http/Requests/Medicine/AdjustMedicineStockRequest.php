<?php

namespace App\Http\Requests\Medicine;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdjustMedicineStockRequest extends FormRequest
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
            'quantity' => ['required', 'integer', 'not_in:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'The quantity is required.',
            'quantity.integer' => 'The quantity must be an integer.',
            'quantity.not_in' => 'The quantity must not be 0.',

            'note.string' => 'The note must be a string.',
            'note.max' => 'The note must be at most 1000 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'quantity' => 'Quantity',
            'note' => 'Note',
        ];
    }
}
