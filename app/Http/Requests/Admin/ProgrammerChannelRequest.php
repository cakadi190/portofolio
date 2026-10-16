<?php

namespace App\Http\Requests\Admin;

use App\Enums\ChannelPlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgrammerChannelRequest extends FormRequest
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
            'description_en' => ['nullable', 'string', 'max:1000'],
            'name' => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::enum(ChannelPlatform::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'url' => ['required', 'url:http,https', 'max:500'],
            'is_featured' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'platform' => 'platform',
            'description' => 'deskripsi',
            'url' => 'tautan channel',
        ];
    }
}
