<?php

namespace App\Http\Requests\Admin;

use App\Enums\CommunityPlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DevCommunityRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::enum(CommunityPlatform::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'invite_url' => ['required', 'url:http,https', 'max:500'],
            'member_count' => ['nullable', 'integer', 'min:0'],
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
            'invite_url' => 'tautan undangan',
            'member_count' => 'jumlah anggota',
        ];
    }
}
