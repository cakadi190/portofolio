<?php

namespace App\Casts\EncryptionModels;

use InvalidArgumentException;
use Stringable;

/**
 * Encrypted cast for integer/float values. Records the original numeric
 * format (int vs float) in the payload meta so it can be restored exactly.
 */
class EncryptedNumberCast extends BaseEncryptedCast
{
    /**
     * Payload type tag stored alongside encrypted numeric values.
     */
    public const TYPE = 'number';

    /**
     * @return string The payload type tag for this cast ({@see self::TYPE}).
     */
    public static function type(): string
    {
        return self::TYPE;
    }

    /**
     * Normalize the value to int|float and record its format in meta.
     *
     * @param  mixed  $value  An int, float, numeric string, or Stringable numeric value.
     * @return array{type:string,value:int|float,meta:array{format:string}} The
     *                                                                      payload ready to be JSON-encoded and encrypted.
     *
     * @throws InvalidArgumentException if the value isn't numeric-compatible.
     */
    public static function normalizeForStorage(mixed $value): array
    {
        $value = self::normalizeNumeric($value);

        $meta = ['format' => is_int($value) ? 'int' : 'float'];

        return [
            'type' => self::TYPE,
            'value' => $value,
            'meta' => $meta,
        ];
    }

    /**
     * Restore the value, casting to the recorded format when available,
     * else falling back to the raw numeric/scalar value.
     *
     * @param  array{type:string,value:mixed,meta?:array{format?:string}}  $payload
     * @return int|float|mixed|null The restored numeric value, the raw
     *                              payload value if it isn't numeric, or null if absent.
     */
    public static function restoreFromPayload(array $payload): mixed
    {
        $value = $payload['value'] ?? null;

        if ($value === null) {
            return null;
        }

        $format = $payload['meta']['format'] ?? null;

        if ($format === 'int') {
            return (int) $value;
        }

        if ($format === 'float') {
            return (float) $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        return is_numeric($value) ? $value + 0 : $value;
    }

    /**
     * Coerce a Stringable/numeric-string/int/float value to int|float.
     *
     * @param  mixed  $value  The raw value to coerce.
     * @return int|float The coerced numeric value.
     *
     * @throws InvalidArgumentException if the value isn't numeric-compatible.
     */
    private static function normalizeNumeric(mixed $value): int|float
    {
        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (is_string($value)) {
            if (! is_numeric($value)) {
                throw new InvalidArgumentException('EncryptedNumberCast expects a numeric-compatible value.');
            }

            $value = $value + 0;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        throw new InvalidArgumentException('EncryptedNumberCast expects an integer or float value.');
    }
}
