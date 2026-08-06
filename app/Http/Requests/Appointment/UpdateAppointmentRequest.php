<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
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
            'scheduled_at' => ['sometimes', 'date', 'after_or_equal:today'],
            'reason' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.date' => 'The scheduled time must be a valid date.',
            'scheduled_at.after_or_equal' => 'The scheduled time must be today or a future date.',

            'reason.string' => 'The reason field must be a string.',
        ];
    }

    public function attributes(): array
    {
        return [
            'scheduled_at' => 'Scheduled Time',
            'reason' => 'Reason',
        ];
    }
}
