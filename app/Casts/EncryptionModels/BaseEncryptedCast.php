<?php

namespace App\Casts\EncryptionModels;

use App\Casts\EncryptionModels\Concerns\EncryptsJsonPayload;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for single-type encrypted attribute casts.
 *
 * Wraps the attribute value in a typed, encrypted JSON payload (via
 * {@see EncryptsJsonPayload}) so it can be safely round-tripped through
 * storage. Concrete subclasses only need to describe their own type tag
 * and how to normalize/restore their value.
 */
abstract class BaseEncryptedCast implements CastsAttributes
{
    use EncryptsJsonPayload;

    /**
     * @return string The payload type tag identifying this cast, e.g. "string", "number".
     */
    abstract public static function type(): string;

    /**
     * Convert a raw attribute value into a storable payload shape.
     *
     * @param  mixed  $value  The raw value assigned to the model attribute.
     * @return array{type:string,value:mixed,meta?:array<string,mixed>} The
     *                                                                  payload to be JSON-encoded and encrypted.
     */
    abstract public static function normalizeForStorage(mixed $value): array;

    /**
     * Rebuild the original PHP value from a decrypted payload.
     *
     * @param  array{type:string,value:mixed,meta?:array<string,mixed>}  $payload  The decoded, decrypted payload.
     * @return mixed The restored attribute value.
     */
    abstract public static function restoreFromPayload(array $payload): mixed;

    /**
     * Decrypt and restore the attribute value for reading.
     *
     * Falls back to the raw stored value if decryption fails, and to the
     * payload's bare value if the payload's type tag doesn't match this
     * cast's own type (e.g. legacy/mismatched data).
     *
     * @param  Model  $model  The Eloquent model instance being hydrated.
     * @param  string  $key  The attribute name being cast.
     * @param  mixed  $value  The raw (encrypted) value read from the database.
     * @param  array<string, mixed>  $attributes  The model's raw attribute array.
     * @return mixed The restored attribute value, or null/raw value on decryption failure.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $payload = $this->decryptPayload($value);

        if ($payload === null) {
            return $value;
        }

        if (($payload['type'] ?? null) !== static::type()) {
            return $payload['value'] ?? $payload;
        }

        return static::restoreFromPayload($payload);
    }

    /**
     * Normalize and encrypt the attribute value for storage.
     *
     * @param  Model  $model  The Eloquent model instance being persisted.
     * @param  string  $key  The attribute name being cast.
     * @param  mixed  $value  The raw value assigned to the attribute.
     * @param  array<string, mixed>  $attributes  The model's raw attribute array.
     * @return string|null The encrypted payload string, or null if $value is null.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $payload = static::normalizeForStorage($value);

        return $this->encryptPayload($payload);
    }
}
