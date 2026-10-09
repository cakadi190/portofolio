<?php

namespace App\Http\Requests\Admin;

use App\Enums\SpeakingFormat;
use App\Enums\SpeakingRole;
use App\Rules\MediaPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SpeakingEngagementRequest extends FormRequest
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
            'organizer' => ['required', 'string', 'max:255'],
            'role' => ['required', new Enum(SpeakingRole::class)],
            'format' => ['required', new Enum(SpeakingFormat::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'registration_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'poster' => ['nullable', 'string', new MediaPath($this->route('speakingEngagement')?->poster)],
            'is_published' => ['required', 'boolean'],
        ];
    }
}
