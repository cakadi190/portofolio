<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CertificationRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'issuer' => ['required', 'string', 'max:255'],
            'issued_at' => ['required', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'credential_id' => ['nullable', 'string', 'max:255'],
            'credential_url' => ['nullable', 'url', 'max:255'],
            'file' => [
                $this->isMethod('POST') ? 'required' : 'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }
}
