<?php

namespace App\Http\Requests\Admin;

use App\Enums\SystemSettingGroup;
use App\Models\SystemSetting;
use App\Rules\MediaPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemSettingRequest extends FormRequest
{
    /** Google tag / measurement ID formats (GA4, Google Ads, GTM, legacy UA). */
    public const string GTAG_PATTERN = '/^(G|GT|GTM|AW|UA)-[A-Z0-9]+(-[A-Z0-9]+)?$/i';

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
                'toggle' => ['in:0,1'],
                'integer' => ['integer', 'min:'.($field['min'] ?? 1), 'max:'.($field['max'] ?? 3650)],
                'select' => [Rule::in(array_column($field['options'] ?? [], 'value'))],
                'email' => ['email'],
                'url' => ['url:http,https'],
                'tel' => ['regex:/^[0-9+\-\s()]+$/'],
                'gtag' => ['regex:'.self::GTAG_PATTERN],
                'digits' => ['regex:/^[0-9]+$/'],
                'image' => [new MediaPath(SystemSetting::query()->where('key', $field['key'])->value('value'))],
                default => [],
            }];
        }

        foreach (SystemSettingGroup::secretKeys() as $key) {
            $rules[$key.'__clear'] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    /**
     * Settings to persist. A blank secret keeps the stored value; the
     * `{key}__clear` flag removes it, so an unchanged masked form can never
     * overwrite a credential.
     *
     * @return array<string, string|null>
     */
    public function settingValues(): array
    {
        $validated = $this->validated();
        $values = [];

        foreach ($validated as $key => $value) {
            if (str_ends_with($key, '__clear')) {
                continue;
            }

            if (in_array($key, SystemSettingGroup::secretKeys(), true)) {
                if (! empty($validated[$key.'__clear'])) {
                    $values[$key] = null;
                } elseif ($value !== null) {
                    $values[$key] = $value;
                }

                continue;
            }

            $values[$key] = $value;
        }

        return $values;
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
        return [
            'google_analytics_id.regex' => 'Google Analytics ID harus berformat seperti G-XXXXXXX, GTM-XXXXXXX, atau AW-XXXXXXX.',
            'facebook_app_id.regex' => 'Facebook App ID hanya boleh berisi angka.',
            '*.regex' => ':attribute hanya boleh berisi angka, spasi, +, - dan tanda kurung.',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function fields(): array
    {
        return array_merge(...array_map(
            static fn (SystemSettingGroup $group): array => $group->fields(),
            SystemSettingGroup::cases(),
        ));
    }
}
