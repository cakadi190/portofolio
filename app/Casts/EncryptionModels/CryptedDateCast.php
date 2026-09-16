<?php

namespace App\Casts\EncryptionModels;

use DateTime;
use Exception;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * Legacy standalone cast that encrypts a date as a plain "Y-m-d" string
 * with {@see Crypt}, with no type payload wrapper.
 *
 * Unlike {@see EncryptedDateCast} (used internally by {@see CryptedUnionCast}),
 * this cast stores no metadata (e.g. timezone) and silently returns null on
 * decryption failure instead of surfacing the raw value.
 */
class CryptedDateCast implements CastsAttributes
{
    /**
     * Decode & convert encrypted date string to Carbon instance.
     *
     * @param  Model  $model  The Eloquent model instance being hydrated.
     * @param  string  $key  The attribute name being cast.
     * @param  mixed  $value  The raw (encrypted) value read from the database.
     * @param  array<string, mixed>  $attributes  The model's raw attribute array.
     * @return Carbon|null The decrypted date, or null on decryption/parse failure.
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($value);

            return Carbon::parse($decrypted);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Encrypt the date before saving to database.
     *
     * @param  Model  $model  The Eloquent model instance being persisted.
     * @param  string  $key  The attribute name being cast.
     * @param  DateTime|Carbon|string|null  $value  The date value assigned to the attribute.
     * @param  array<string, mixed>  $attributes  The model's raw attribute array.
     * @return string|null The encrypted "Y-m-d" string, or null if $value is null.
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Carbon) {
            $value = Carbon::parse($value);
        }

        $dateString = $value->format('Y-m-d');

        return Crypt::encryptString($dateString);
    }
}
