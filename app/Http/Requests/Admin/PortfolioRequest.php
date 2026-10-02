<?php

namespace App\Http\Requests\Admin;

use App\Rules\MediaPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PortfolioRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('portfolios', 'slug')->ignore($this->route('portfolio'))],
            'image' => ['required', 'string', new MediaPath($this->route('portfolio')?->image)],
            'short_desc' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'demo_link' => ['nullable', 'url', 'max:255'],
            'source_code' => ['nullable', 'url', 'max:255'],
            'is_private' => ['boolean'],
            'technologies' => ['nullable', 'array'],
            'technologies.*' => ['integer', 'exists:technologies,id'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:portfolio_categories,id'],
            'careers' => ['nullable', 'array'],
            'careers.*' => ['integer', 'exists:careers,id'],
            'galleries' => ['nullable', 'array'],
            'galleries.*.image_url' => ['required', 'string', new MediaPath($this->route('portfolio')?->galleries()->pluck('image_url')->all())],
            'galleries.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
