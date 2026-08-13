<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexInvoiceRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(['unpaid', 'paid', 'cancelled'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: unpaid, paid, cancelled.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page must not exceed 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'Status',
            'per_page' => 'Per Page',
        ];
    }
}
