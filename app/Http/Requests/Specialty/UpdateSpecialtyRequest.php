<?php

namespace App\Http\Requests\Specialty;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpecialtyRequest extends FormRequest
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
        $specialty = $this->route('specialty');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('specialties', 'name')->ignore($specialty)],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Tên chuyên khoa đã tồn tại.',
            'name.max' => 'Tên chuyên khoa không được vượt quá 255 ký tự.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên chuyên khoa',
            'description' => 'Mô tả chuyên khoa',
        ];
    }
}
