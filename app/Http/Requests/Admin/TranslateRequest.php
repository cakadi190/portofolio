<?php

namespace App\Http\Requests\Admin;

use App\Support\AppLocale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TranslateRequest extends FormRequest
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
            'text' => ['required', 'string', 'max:200000'],
            'from' => ['required', 'string', Rule::in(AppLocale::available())],
            'to' => ['required', 'string', Rule::in(AppLocale::available()), 'different:from'],
            'format' => ['required', 'string', Rule::in(['html', 'text'])],
        ];
    }
}
