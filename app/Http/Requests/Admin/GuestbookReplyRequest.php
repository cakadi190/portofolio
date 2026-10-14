<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\SanitizesRichText;
use Illuminate\Foundation\Http\FormRequest;

class GuestbookReplyRequest extends FormRequest
{
    use SanitizesRichText;

    public function authorize(): bool
    {
        return $this->user()?->canManageBlog() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['body' => 'balasan'];
    }

    protected function prepareForValidation(): void
    {
        $this->sanitizeRichText('body');
    }
}
