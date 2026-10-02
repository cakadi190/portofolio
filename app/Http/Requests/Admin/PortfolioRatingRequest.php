<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PortfolioRatingRequest extends FormRequest
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
            'reviewer_name' => ['required', 'string', 'max:100'],
            'reviewer_email' => ['required', 'email', 'max:255'],
            'reviewer_company' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:150'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string'],
            'is_approved' => ['boolean'],
        ];
    }
}
