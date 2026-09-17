<?php

namespace App\Models;

use App\Enums\SocialiteProvider;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    /**
     * The credential/config fields managed per SocialiteProvider case.
     * Combined with a provider's value via {@see self::socialiteKey()} to
     * form the actual SystemSetting `key` column values, e.g.
     * `socialite_google_client_id`.
     *
     * @var list<string>
     */
    public const array SOCIALITE_FIELDS = ['client_id', 'client_secret', 'is_active'];

    /**
     * Build the SystemSetting `key` for a single Socialite provider field.
     *
     * @param  SocialiteProvider  $provider  The provider the field belongs to.
     * @param  string  $field  One of {@see self::SOCIALITE_FIELDS} (e.g. `'client_id'`).
     * @return string The SystemSetting key, e.g. `socialite_google_client_id`.
     */
    public static function socialiteKey(SocialiteProvider $provider, string $field): string
    {
        return "socialite_{$provider->value}_{$field}";
    }

    protected $fillable = ['key', 'value'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
