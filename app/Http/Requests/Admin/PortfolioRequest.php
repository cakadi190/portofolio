<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\SanitizesRichText;
use App\Rules\MediaPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PortfolioRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('portfolios', 'slug')->ignore($this->route('portfolio'))],
            'image' => ['required', 'string', new MediaPath($this->route('portfolio')?->image)],
            'short_desc' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'short_desc_en' => ['nullable', 'string', 'max:255'],
            'description_en' => ['nullable', 'string'],
            'demo_link' => ['nullable', 'url', 'max:255'],
            'source_code' => ['nullable', 'url', 'max:255'],
            'is_private' => ['boolean'],
            'technologies' => ['nullable', 'array'],
            'technologies.*' => ['integer', 'exists:technologies,id'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
            'careers' => ['nullable', 'array'],
            'careers.*' => ['integer', 'exists:careers,id'],
            'galleries' => ['nullable', 'array'],
            'galleries.*.image_url' => ['required', 'string', new MediaPath($this->route('portfolio')?->galleries()->pluck('image_url')->all())],
            'galleries.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
