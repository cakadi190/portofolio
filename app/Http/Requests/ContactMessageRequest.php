<?php

namespace App\Http\Requests;

use App\Enums\ContactReason;
use App\Http\Requests\Concerns\SanitizesRichText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactMessageRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'reason' => ['required', Rule::enum(ContactReason::class)],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'email' => 'email',
            'reason' => 'keperluan',
            'message' => 'pesan',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->sanitizeRichText('message');
    }
}
