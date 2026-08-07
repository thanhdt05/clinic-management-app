<?php

namespace App\Http\Requests\Examination;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExaminationRequest extends FormRequest
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
            'diagnosis' => ['sometimes', 'string', 'max:1000'],
            'notes' => ['sometimes', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'diagnosis.string' => 'Diagnosis must be a string.',
            'diagnosis.max' => 'Diagnosis must not exceed 1000 characters.',

            'notes.string' => 'Notes must be a string.',
            'notes.max' => 'Notes must not exceed 1000 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'diagnosis' => 'Diagnosis',
            'notes' => 'Notes',
        ];
    }
}
