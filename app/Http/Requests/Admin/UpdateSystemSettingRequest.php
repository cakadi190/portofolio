<?php

namespace App\Http\Requests\Admin;

use App\Enums\SystemSettingGroup;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (self::fields() as $field) {
            $rules[$field['key']] = ['nullable', 'string', 'max:255', ...match ($field['type']) {
                'email' => ['email'],
                'url' => ['url:http,https'],
                'tel' => ['regex:/^[0-9+\-\s()]+$/'],
                default => [],
            }];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_column(self::fields(), 'label', 'key');
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['*.regex' => ':attribute hanya boleh berisi angka, spasi, +, - dan tanda kurung.'];
    }

    /**
     * @return list<array{key: string, label: string, type: string, placeholder: string, note?: string}>
     */
    private static function fields(): array
    {
        return array_merge(...array_map(
            static fn (SystemSettingGroup $group): array => $group->fields(),
            SystemSettingGroup::cases(),
        ));
    }
}
