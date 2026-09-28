<?php

namespace App\Http\Requests\Admin;

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
        $isCreate = $this->isMethod('post');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('portfolios', 'slug')->ignore($this->route('portfolio'))],
            'image' => [$isCreate ? 'required' : 'nullable', 'image', 'max:4096'],
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
        ];
    }
}
