<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\SanitizesRichText;
use App\Rules\MediaPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    use SanitizesRichText;

    protected function prepareForValidation(): void
    {
        $this->sanitizeEditorHtml(['description', 'description_en']);
    }

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
            'slug' => ['required', 'string', 'max:255', Rule::unique('services', 'slug')->ignore($this->route('service'))],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'image' => ['nullable', 'string', new MediaPath($this->route('service')?->image)],
            'description' => ['nullable', 'string'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_en' => ['nullable', 'string'],
        ];
    }
}
