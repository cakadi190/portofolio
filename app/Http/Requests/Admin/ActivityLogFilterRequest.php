<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityLogFilterRequest extends FormRequest
{
    public const array PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'log_name' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', 'string', 'max:100'],
            'outcome' => ['nullable', Rule::in(['success', 'failure'])],
            'causer_id' => ['nullable', 'integer', 'min:1'],
            'subject_type' => ['nullable', 'string', 'max:150'],
            'subject_id' => ['nullable', 'integer', 'min:1'],
            'ip_address' => ['nullable', 'ip'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'sort' => ['nullable', Rule::in(['created_at', 'log_name', 'event'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_filter($this->safe()->only(['search', 'log_name', 'event', 'outcome', 'causer_id', 'subject_type', 'subject_id', 'ip_address', 'from', 'to']), fn ($v) => $v !== null && $v !== '');
    }
}
