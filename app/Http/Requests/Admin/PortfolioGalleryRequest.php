<?php

namespace App\Http\Requests\Admin;

use App\Rules\MediaPath;
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
        return [
            'portfolio_id' => ['required', 'integer', 'exists:portfolios,id'],
            'image_url' => ['required', 'string', new MediaPath($this->route('portfolioGallery')?->image_url)],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
