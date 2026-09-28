<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'website' => ['nullable', 'url', 'max:255'],
            'level' => ['required', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'study_program' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'place' => ['required', 'string', 'max:255'],
            'academic_score_type' => ['nullable', 'string', 'max:255'],
            'academic_score_label' => ['nullable', 'string', 'max:255'],
            'academic_score_value' => ['nullable', 'numeric', 'min:0'],
            'academic_score_scale' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
