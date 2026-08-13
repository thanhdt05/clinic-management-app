<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
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
            'examination_id' => ['required', 'integer', 'exists:examinations,id', 'unique:invoices,examination_id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'examination_id.required' => 'Examination is required.',
            'examination_id.integer' => 'Examination must be an integer.',
            'examination_id.exists' => 'Examination does not exist.',
            'examination_id.unique' => 'Examination already has an invoice.',

            'discount.numeric' => 'Discount must be a number.',
            'discount.min' => 'Discount must be at least 0.',
        ];
    }

    public function attributes(): array
    {
        return [
            'examination_id' => 'Examination',
            'discount' => 'Discount',
        ];
    }
}
