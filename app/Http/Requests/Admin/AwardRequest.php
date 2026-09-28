<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AwardRequest extends FormRequest
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
            'event_name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'rank' => ['nullable', 'integer', 'min:1', 'max:255'],
            'awarded_at' => ['nullable', 'date'],
        ];
    }
}
