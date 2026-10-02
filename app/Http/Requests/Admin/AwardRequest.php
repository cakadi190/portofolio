<?php

namespace App\Http\Requests\Admin;

use App\Enums\AwardType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
            'type' => ['required', new Enum(AwardType::class)],
            'year' => ['required', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'rank' => ['nullable', 'integer', 'min:1', 'max:255'],
            'awarded_at' => ['nullable', 'date'],
        ];
    }
}
