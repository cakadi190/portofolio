<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\ValidatesTranslations;
use App\Rules\MediaPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    use ValidatesTranslations;

    /** @var list<string> */
    public const array TRANSLATED = ['title', 'excerpt', 'content'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeTranslations(self::TRANSLATED);
    }

    /**
     * Validated data with blank non-default translations dropped.
     *
     * @return array<string, mixed>
     */
    public function translatedData(): array
    {
        return $this->withoutBlankTranslations($this->safe()->except(['tags', 'categories']), self::TRANSLATED);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->translatedRules('title', ['string', 'max:255']),
            ...$this->translatedRules('excerpt', ['string', 'max:255'], required: false),
            ...$this->translatedRules('content', ['string']),
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereIn('account_type', [UserRole::Admin->value, UserRole::Redaktur->value])],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($this->route('post'))],
            'cover_image' => ['nullable', 'string', new MediaPath($this->route('post')?->cover_image)],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:post_categories,id'],
        ];
    }
}
