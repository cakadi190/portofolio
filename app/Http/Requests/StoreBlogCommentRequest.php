<?php

namespace App\Http\Requests;

use App\Enums\BlogCommentStatus;
use App\Http\Requests\Concerns\SanitizesRichText;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlogCommentRequest extends FormRequest
{
    use SanitizesRichText;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * A reply must target an approved comment of this very post; the client
     * cannot attach a comment to another post's thread.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Post $post */
        $post = $this->route('post');

        return [
            'guest_name' => [Rule::requiredIf($this->user() === null), 'nullable', 'string', 'max:100'],
            'guest_email' => [Rule::requiredIf($this->user() === null), 'nullable', 'email', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('blog_comments', 'id')
                    ->where('post_id', $post->id)
                    ->where('status', BlogCommentStatus::Approved->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'guest_name' => 'nama',
            'guest_email' => 'email',
            'body' => 'komentar',
            'parent_id' => 'komentar yang dibalas',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->sanitizeRichText('body');
    }
}
