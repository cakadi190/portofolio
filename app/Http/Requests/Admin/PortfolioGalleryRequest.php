<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PortfolioGalleryRequest extends FormRequest
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
            'portfolio_id' => ['required', 'integer', 'exists:portfolios,id'],
            'image' => [$isCreate ? 'required' : 'nullable', 'image', 'max:5120'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
