<?php

namespace App\Casts\EncryptionModels;

/**
 * Encrypted cast for plain string values; the fallback branch of
 * {@see CryptedUnionCast} when a value isn't numeric, date-like, or JSON.
 */
class EncryptedStringCast extends BaseEncryptedCast
{
    /**
     * Payload type tag stored alongside encrypted string values.
     */
    public const TYPE = 'string';

    /**
     * @return string The payload type tag for this cast ({@see self::TYPE}).
     */
    public static function type(): string
    {
        return self::TYPE;
    }

    /**
     * Coerce the value to a string (via __toString for Stringable/objects)
     * before wrapping it in the payload.
     *
     * @param  mixed  $value  Any value coercible to string.
     * @return array{type:string,value:string} The payload ready to be
     *                                         JSON-encoded and encrypted.
     */
    public static function normalizeForStorage(mixed $value): array
    {
        return [
            'type' => self::TYPE,
            'value' => (string) $value,
        ];
    }

    /**
     * @param  array{type:string,value:mixed,meta?:array<string,mixed>}  $payload
     * @return string The restored string, or an empty string if the value is absent.
     */
    public static function restoreFromPayload(array $payload): mixed
    {
        return (string) ($payload['value'] ?? '');
    }
}
