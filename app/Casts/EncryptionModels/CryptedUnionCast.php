<?php

namespace App\Casts\EncryptionModels;

use App\Casts\EncryptionModels\Concerns\EncryptsJsonPayload;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use JsonException;
use JsonSerializable;
use Stringable;
use Throwable;

/**
 * Attribute cast that auto-detects the value's type (string, number, date,
 * or JSON-like) and delegates to the matching Encrypted*Cast to normalize,
 * encrypt, and restore it.
 *
 * Unlike {@see BaseEncryptedCast} subclasses, callers don't have to know or
 * declare the value's type up front — it is inferred from the runtime value
 * on write and recovered from the payload's type tag on read.
 */
class CryptedUnionCast implements CastsAttributes
{
    use EncryptsJsonPayload;

    /**
     * Decrypt the payload and restore it using the cast matching its type tag.
     *
     * @param  Model  $model  The Eloquent model instance being hydrated.
     * @param  string  $key  The attribute name being cast.
     * @param  mixed  $value  The raw (encrypted) value read from the database.
     * @param  array<string, mixed>  $attributes  The model's raw attribute array.
     * @return mixed The restored value (string/int/float/Carbon/array/etc.),
     *               or the raw stored value if decryption fails.
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

        $type = $payload['type'] ?? null;

        return match ($type) {
            EncryptedStringCast::TYPE => EncryptedStringCast::restoreFromPayload($payload),
            EncryptedNumberCast::TYPE => EncryptedNumberCast::restoreFromPayload($payload),
            EncryptedDateCast::TYPE => EncryptedDateCast::restoreFromPayload($payload),
            EncryptedJsonCast::TYPE => EncryptedJsonCast::restoreFromPayload($payload),
            default => $payload['value'] ?? $value,
        };
    }

    /**
     * Detect the value's type, normalize it into a payload, and encrypt it.
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

        $payload = $this->buildPayload($value);

        return $this->encryptPayload($payload);
    }

    /**
     * Route the value to the matching Encrypted*Cast::normalizeForStorage(),
     * checked in order: date, numeric, JSON-like, then plain string as the
     * fallback.
     *
     * @param  mixed  $value  The raw value assigned to the attribute.
     * @return array{type:string,value:mixed,meta?:array<string,mixed>} The
     *                                                                  payload produced by the matching Encrypted*Cast.
     */
    private function buildPayload(mixed $value): array
    {
        if ($this->isDateValue($value)) {
            return EncryptedDateCast::normalizeForStorage($value);
        }

        if ($this->isNumericValue($value)) {
            return EncryptedNumberCast::normalizeForStorage($value);
        }

        if ($this->isJsonValue($value)) {
            return EncryptedJsonCast::normalizeForStorage($value);
        }

        return EncryptedStringCast::normalizeForStorage($value);
    }

    /**
     * True for raw int/float values only — numeric strings are treated as
     * plain strings to avoid misreading things like zero-padded codes.
     *
     * @param  mixed  $value  The value to test.
     * @return bool True if the value should be routed to {@see EncryptedNumberCast}.
     */
    private function isNumericValue(mixed $value): bool
    {
        if ($value instanceof Stringable) {
            return false;
        }

        return is_int($value) || is_float($value);
    }

    /**
     * True for Carbon/DateTimeInterface instances, or strings that contain
     * a digit plus a date-like separator (-, :, /, or whitespace) and parse
     * successfully via {@see Carbon::parse()}.
     *
     * @param  mixed  $value  The value to test.
     * @return bool True if the value should be routed to {@see EncryptedDateCast}.
     */
    private function isDateValue(mixed $value): bool
    {
        if ($value instanceof Carbon) {
            return true;
        }

        if ($value instanceof DateTimeInterface) {
            return true;
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            return false;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return false;
        }

        if (! preg_match('/\d/', $normalized)) {
            return false;
        }

        if (! preg_match('/[-:\/\s]/', $normalized)) {
            return false;
        }

        try {
            Carbon::parse($normalized);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * True for arrays, booleans, null, Jsonable/JsonSerializable/Arrayable
     * objects, plain objects (excluding dates), or strings that look like
     * a JSON object/array.
     *
     * @param  mixed  $value  The value to test.
     * @return bool True if the value should be routed to {@see EncryptedJsonCast}.
     */
    private function isJsonValue(mixed $value): bool
    {
        if (is_int($value) || is_float($value)) {
            return false;
        }

        if ($value instanceof Jsonable || $value instanceof JsonSerializable || $value instanceof Arrayable) {
            return true;
        }

        if (is_array($value) || is_bool($value) || $value === null) {
            return true;
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (is_string($value)) {
            return $this->stringLooksLikeJson($value);
        }

        if (is_object($value) && ! $value instanceof DateTimeInterface) {
            return true;
        }

        return false;
    }

    /**
     * True if the trimmed string starts with { or [ and parses as valid JSON.
     *
     * @param  string  $value  The candidate string.
     * @return bool True if the string looks like and successfully parses as JSON.
     */
    private function stringLooksLikeJson(string $value): bool
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return false;
        }

        if (! in_array($trimmed[0], ['{', '['], true)) {
            return false;
        }

        try {
            json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);

            return true;
        } catch (JsonException) {
            return false;
        }
    }
}
