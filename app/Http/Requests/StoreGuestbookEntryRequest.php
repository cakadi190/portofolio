<?php

namespace App\Http\Requests;

use App\Enums\BlogCommentStatus;
use App\Http\Requests\Concerns\SanitizesRichText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuestbookEntryRequest extends FormRequest
{
    use SanitizesRichText;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Signed-out visitors leave a name and email (like the contact form); a reply must target an approved entry.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'guest_name' => [Rule::requiredIf($this->user() === null), 'nullable', 'string', 'max:100'],
            'guest_email' => [Rule::requiredIf($this->user() === null), 'nullable', 'email', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('guestbook_entries', 'id')->where('status', BlogCommentStatus::Approved->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return trans('validation.forms.guestbook');
    }

    protected function prepareForValidation(): void
    {
        $this->sanitizeRichText('body');
    }
}
