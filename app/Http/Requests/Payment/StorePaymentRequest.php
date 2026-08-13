<?php

namespace App\Http\Requests\Payment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', Rule::in(['visa', 'paypal'])],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'The amount is required',
            'amount.numeric' => 'The amount must be a number',
            'amount.gt' => 'The amount must be greater than 0',

            'method.required' => 'The method is required',
            'method.in' => 'The method must be either visa or paypal',

            'note.max' => 'The note cannot exceed 500 characters',
            'note.string' => 'The note must be a string',
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => 'Amount',
            'method' => 'Payment Method',
            'note' => 'Note',
        ];
    }
}
