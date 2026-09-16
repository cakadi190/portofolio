<?php

namespace App\Casts\EncryptionModels;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Stringable;

/**
 * Encrypted cast for date/datetime values. Stores the value as an ISO 8601
 * string plus its original timezone, and restores it as a {@see Carbon}
 * instance.
 */
class EncryptedDateCast extends BaseEncryptedCast
{
    /**
     * Payload type tag stored alongside encrypted date values.
     */
    public const TYPE = 'date';

    /**
     * @return string The payload type tag for this cast ({@see self::TYPE}).
     */
    public static function type(): string
    {
        return self::TYPE;
    }

    /**
     * Resolve the value to a Carbon instance and serialize it to ISO 8601,
     * preserving its timezone in meta.
     *
     * @param  mixed  $value  A Carbon/DateTimeInterface/Stringable/string date value.
     * @return array{type:string,value:string,meta:array{timezone:string}} The
     *                                                                     payload ready to be JSON-encoded and encrypted.
     *
     * @throws InvalidArgumentException if the value can't be resolved to a date.
     */
    public static function normalizeForStorage(mixed $value): array
    {
        $date = self::resolveDate($value);

        return [
            'type' => self::TYPE,
            'value' => $date->toIso8601String(),
            'meta' => [
                'timezone' => $date->getTimezone()->getName(),
            ],
        ];
    }

    /**
     * @param  array{type:string,value:mixed,meta?:array<string,mixed>}  $payload
     * @return Carbon|null The restored date, or null if the stored value is absent.
     */
    public static function restoreFromPayload(array $payload): mixed
    {
        $value = $payload['value'] ?? null;

        if ($value === null) {
            return null;
        }

        return Carbon::parse($value);
    }

    /**
     * Accept Carbon/DateTimeInterface/Stringable/string inputs.
     *
     * @param  mixed  $value  The raw value to resolve into a date.
     * @return Carbon The resolved Carbon instance.
     *
     * @throws InvalidArgumentException if the value can't be resolved to a date.
     */
    private static function resolveDate(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::parse($value);
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (is_string($value)) {
            return Carbon::parse($value);
        }

        throw new InvalidArgumentException('EncryptedDateCast expects a date-compatible value.');
    }
}
