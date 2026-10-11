<?php

namespace App\Http\Requests\Admin;

use App\Enums\ClarificationVerdict;
use App\Rules\MediaPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ClarificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Drop untouched rows of the repeatable source and update lists so the
     * editor can keep a blank row at the bottom.
     */
    protected function prepareForValidation(): void
    {
        $plainTextFields = ['claim', 'claim_origin', 'summary', 'apology', 'withdrawal_note'];

        $this->merge(collect($plainTextFields)
            ->filter(fn (string $field): bool => is_string($this->input($field)))
            ->mapWithKeys(fn (string $field): array => [$field => trim(strip_tags($this->input($field)))])
            ->all());

        $this->merge([
            'sources' => $this->filledRows('sources'),
            'updates' => $this->filledRows('updates'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isPublishing = $this->boolean('is_published');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('clarifications', 'slug')->ignore($this->route('clarification'))],
            'claim' => ['required', 'string', 'max:1000'],
            'claim_origin' => ['nullable', 'string', 'max:255'],
            'verdict' => ['required', new Enum(ClarificationVerdict::class)],
            'summary' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'apology' => [
                Rule::requiredIf($this->input('verdict') === ClarificationVerdict::TrueWithApology->value),
                'nullable', 'string', 'max:5000',
            ],
            'cover_image' => ['nullable', 'string', new MediaPath($this->route('clarification')?->cover_image)],
            'sources' => [$isPublishing ? 'required' : 'nullable', 'array', 'max:20'],
            'sources.*.title' => ['required', 'string', 'max:255'],
            'sources.*.url' => ['required', 'url', 'max:2048'],
            'updates' => ['nullable', 'array', 'max:50'],
            'updates.*.date' => ['required', 'date'],
            'updates.*.note' => ['required', 'string', 'max:2000'],
            'is_withdrawn' => ['nullable', 'boolean'],
            'withdrawal_note' => ['nullable', 'string', 'max:2000'],
            'is_published' => ['required', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Rows of a repeatable field that have at least one filled value, reindexed.
     *
     * @return list<array<string, mixed>>
     */
    private function filledRows(string $key): array
    {
        $rows = $this->input($key);

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter(
            $rows,
            fn (mixed $row): bool => is_array($row) && array_filter($row, fn (mixed $value): bool => filled($value)) !== [],
        ));
    }
}
