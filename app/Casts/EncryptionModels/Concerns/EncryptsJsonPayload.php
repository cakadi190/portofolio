<?php

namespace App\Casts\EncryptionModels\Concerns;

use Illuminate\Support\Facades\Crypt;
use JsonException;
use Throwable;

/**
 * Shared JSON+encryption plumbing for the Encrypted* and Crypted* attribute
 * casts: serializes a typed payload array to JSON and encrypts it with
 * Laravel's {@see Crypt} facade, and reverses the process on read.
 */
trait EncryptsJsonPayload
{
    /**
     * Encrypt the payload structure to a string representation.
     *
     * Drops an empty `meta` key before encoding, to keep the encrypted
     * payload minimal when a cast has no extra metadata to store.
     *
     * @param  array<string, mixed>  $payload  The typed payload, e.g.
     *                                         `['type' => 'string', 'value' => ..., 'meta' => [...]]`.
     * @return string The encrypted ciphertext, safe to persist to the database.
     */
    private function encryptPayload(array $payload): string
    {
        if (array_key_exists('meta', $payload) && $payload['meta'] === []) {
            unset($payload['meta']);
        }

        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);

        return Crypt::encryptString($encoded);
    }

    /**
     * Decrypt the stored value back into the payload structure.
     *
     * Returns null if decryption fails (e.g. wrong/rotated app key,
     * corrupted data). If decryption succeeds but the plaintext isn't
     * valid JSON or doesn't decode to an array, it is wrapped in a
     * payload shape with a null `type` so callers can still recover the
     * raw decrypted value.
     *
     * @param  string  $value  The encrypted ciphertext read from the database.
     * @return array<string, mixed>|null The decoded payload, a
     *                                   `['type' => null, 'value' => ...]` fallback, or
     *                                   null if decryption failed.
     */
    private function decryptPayload(string $value): ?array
    {
        try {
            $decrypted = Crypt::decryptString($value);
        } catch (Throwable) {
            return null;
        }

        try {
            $decoded = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [
                'type' => null,
                'value' => $decrypted,
            ];
        }

        if (! is_array($decoded)) {
            return [
                'type' => null,
                'value' => $decoded,
            ];
        }

        return $decoded;
    }
}
