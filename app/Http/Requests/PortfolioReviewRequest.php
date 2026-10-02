<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SanitizesRichText;
use Illuminate\Foundation\Http\FormRequest;

class PortfolioReviewRequest extends FormRequest
{
    use SanitizesRichText;

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
            'reviewer_name' => ['required', 'string', 'max:100'],
            'reviewer_email' => ['required', 'email', 'max:255'],
            'reviewer_company' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:150'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reviewer_name' => 'nama',
            'reviewer_email' => 'email',
            'reviewer_company' => 'perusahaan / jabatan',
            'title' => 'judul ulasan',
            'rating' => 'penilaian',
            'comment' => 'isi ulasan',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->sanitizeRichText('comment');
    }
}
