<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\SanitizesRichText;
use App\Rules\MediaPath;
use Illuminate\Foundation\Http\FormRequest;

class ContactMessageReplyRequest extends FormRequest
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
            'subject' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:20000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['string', 'distinct', new MediaPath],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'subject' => 'subjek',
            'body' => 'isi balasan',
            'attachments' => 'lampiran',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->sanitizeRichText('body');
        $this->merge(['subject' => trim(strip_tags((string) $this->input('subject')))]);
    }
}
