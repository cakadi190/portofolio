<?php

namespace App\Casts\EncryptionModels;

use App\Casts\EncryptionModels\Concerns\EncryptsJsonPayload;
use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Attribute cast that stores a backed string enum encrypted at rest.
 *
 * Wraps the same encrypted string payload used by {@see EncryptedStringCast}
 * and resolves it to/from the given enum class, so sensitive-but-typed
 * columns (e.g. user preferences) can keep at-rest encryption while still
 * benefiting from native enum casting.
 *
 * Usage: `'setting_language' => CryptedEnumCast::class.':'.LocaleEnum::class`
 */
class CryptedEnumCast implements CastsAttributes
{
    use EncryptsJsonPayload;

    /**
     * @param  class-string<BackedEnum>  $enumClass  The backed enum class to resolve values into.
     */
    public function __construct(private readonly string $enumClass) {}

    /**
     * Decrypt the payload and resolve it into an instance of the enum class.
     *
     * @param  Model  $model  The Eloquent model instance being hydrated.
     * @param  string  $key  The attribute name being cast.
     * @param  mixed  $value  The raw (encrypted) value read from the database.
     * @param  array<string, mixed>  $attributes  The model's raw attribute array.
     * @return BackedEnum|null The resolved enum instance, or null if the value is missing/invalid.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $payload = $this->decryptPayload($value);

        if ($payload === null) {
            return null;
        }

        $raw = EncryptedStringCast::restoreFromPayload($payload);

        return $this->enumClass::tryFrom($raw);
    }

    /**
     * Normalize the value into the enum's scalar value, then encrypt it as a string payload.
     *
     * @param  Model  $model  The Eloquent model instance being persisted.
     * @param  string  $key  The attribute name being cast.
     * @param  BackedEnum|string|null  $value  The enum instance or raw scalar assigned to the attribute.
     * @param  array<string, mixed>  $attributes  The model's raw attribute array.
     * @return string|null The encrypted payload string, or null if $value is null.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $scalar = $value instanceof BackedEnum ? $value->value : (string) $value;

        $payload = EncryptedStringCast::normalizeForStorage($scalar);

        return $this->encryptPayload($payload);
    }
}
