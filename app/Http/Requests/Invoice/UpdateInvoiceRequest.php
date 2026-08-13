<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
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
            'discount' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'discount.required' => 'Discount is required.',
            'discount.numeric' => 'Discount must be a number.',
            'discount.min' => 'Discount must be at least 0.',
        ];
    }

    public function attributes(): array
    {
        return [
            'discount' => 'Discount',
        ];
    }
}
