<?php

namespace App\Casts\EncryptionModels;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use JsonException;
use JsonSerializable;
use Stringable;

/**
 * Encrypted cast for JSON-like values: arrays, booleans, null,
 * Jsonable/JsonSerializable/Arrayable objects, plain objects, JSON strings,
 * and numeric scalars. Values are decoded to their native PHP
 * array/scalar form before encryption, and returned as-is on read.
 */
class EncryptedJsonCast extends BaseEncryptedCast
{
    /**
     * Payload type tag stored alongside encrypted JSON values.
     */
    public const TYPE = 'json';

    /**
     * @return string The payload type tag for this cast ({@see self::TYPE}).
     */
    public static function type(): string
    {
        return self::TYPE;
    }

    /**
     * Normalize an arbitrary JSON-like value into a storable payload.
     *
     * @param  mixed  $value  Any array, bool, null, Jsonable, JsonSerializable,
     *                        Arrayable, JSON string, plain object, or numeric
     *                        scalar.
     * @return array{type:string,value:mixed,meta:array{source:string}} The
     *                                                                  payload ready to be JSON-encoded and encrypted.
     *
     * @throws InvalidArgumentException if the value can't be represented as JSON.
     */
    public static function normalizeForStorage(mixed $value): array
    {
        [$normalized, $meta] = self::normalizeValue($value);

        return [
            'type' => self::TYPE,
            'value' => $normalized,
            'meta' => $meta,
        ];
    }

    /**
     * Extract the decoded value from a decrypted payload.
     *
     * @param  array{type:string,value:mixed,meta?:array<string,mixed>}  $payload
     * @return mixed The decoded array/scalar value, or null if absent.
     */
    public static function restoreFromPayload(array $payload): mixed
    {
        return $payload['value'] ?? null;
    }

    /**
     * Normalize the value to a JSON-decoded (array/scalar) form, tagging
     * the result with a `source` meta describing where it came from.
     *
     * Checked in order: Jsonable, JsonSerializable, Arrayable, array,
     * bool/null, string (JSON-looking only), other objects (via
     * json_encode), then numeric scalars. Anything else throws.
     *
     * @param  mixed  $value  The raw value passed to normalizeForStorage().
     * @return array{0:mixed,1:array{source:string}} A tuple of
     *                                               [decoded value, meta describing its origin].
     *
     * @throws InvalidArgumentException if the value can't be represented as JSON.
     */
    private static function normalizeValue(mixed $value): array
    {
        if ($value instanceof Jsonable) {
            return [
                self::decodeJson($value->toJson()),
                ['source' => 'jsonable'],
            ];
        }

        if ($value instanceof JsonSerializable) {
            $encoded = json_encode($value, JSON_THROW_ON_ERROR);

            return [
                self::decodeJson($encoded),
                ['source' => 'json_serializable'],
            ];
        }

        if ($value instanceof Arrayable) {
            return [$value->toArray(), ['source' => 'arrayable']];
        }

        if (is_array($value)) {
            return [$value, ['source' => 'array']];
        }

        if (is_bool($value) || $value === null) {
            return [$value, ['source' => 'scalar']];
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (is_string($value)) {
            if (self::looksLikeJson($value)) {
                return [
                    self::decodeJson($value),
                    ['source' => 'json_string'],
                ];
            }

            throw new InvalidArgumentException('EncryptedJsonCast expects JSON string to start with { or [.');
        }

        if (is_object($value)) {
            $encoded = json_encode($value, JSON_THROW_ON_ERROR);

            return [
                self::decodeJson($encoded),
                ['source' => 'object'],
            ];
        }

        if (is_numeric($value)) {
            return [$value + 0, ['source' => 'scalar']];
        }

        throw new InvalidArgumentException('EncryptedJsonCast cannot handle the given value.');
    }

    /**
     * Cheaply guess whether a string is JSON by checking its first
     * non-whitespace character, before attempting a full decode.
     *
     * @param  string  $value  The candidate string.
     * @return bool True if the trimmed string is non-empty and starts with { or [.
     */
    private static function looksLikeJson(string $value): bool
    {
        $trimmed = trim($value);

        return $trimmed !== '' && in_array($trimmed[0], ['{', '['], true);
    }

    /**
     * Decode a JSON string to its native PHP representation (arrays for
     * objects, per the `$associative = true` flag).
     *
     * @param  string  $value  A JSON-encoded string.
     * @return mixed The decoded array/scalar value.
     *
     * @throws InvalidArgumentException if $value isn't valid JSON.
     */
    private static function decodeJson(string $value): mixed
    {
        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('EncryptedJsonCast received invalid JSON.', previous: $exception);
        }
    }
}
