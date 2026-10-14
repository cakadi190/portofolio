<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Enums\Gender;
use App\Rules\MediaPath;
use App\Services\HtmlSanitizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge(['username' => Str::lower(ltrim(trim((string) $this->input('username')), '@'))]);
        }

        if ($this->has('bio')) {
            $this->merge(['bio' => HtmlSanitizer::clean((string) $this->input('bio'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()->id),
            'phone' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', new Enum(Gender::class)],
            'username' => ['sometimes', ...$this->usernameRules($this->user()->id)],
            'bio' => ['nullable', 'string', 'max:5000'],
            'avatar' => ['nullable', 'string', new MediaPath($this->user()->avatar)],
        ];
    }
}
