<?php

namespace App\Http\Requests\Admin;

use App\Enums\BlogCommentStatus;
use App\Http\Requests\Concerns\SanitizesRichText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class GuestbookEntryRequest extends FormRequest
{
    use SanitizesRichText;

    public function authorize(): bool
    {
        return $this->user()?->canManageBlog() ?? false;
    }

    /**
     * Only the text and moderation status are editable; ownership and thread
     * position are immutable.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'status' => ['required', new Enum(BlogCommentStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['body' => 'pesan'];
    }

    protected function prepareForValidation(): void
    {
        $this->sanitizeRichText('body');
    }
}
