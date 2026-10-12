<?php

namespace App\Support;

/**
 * Recursively redact secrets and bound the size of values before they reach
 * the activity log or the admin viewer.
 */
final class ActivitySanitizer
{
    public const string REDACTED = '[REDACTED]';

    private const string SENSITIVE_KEY_PATTERN = '/pass(word|wd|phrase)?|secret|token|api[_-]?key|authorization|cookie|credential|session|otp|two_factor|recovery|card|cvv|signature|private[_-]?key|bearer/i';

    private const int MAX_DEPTH = 6;

    private const int MAX_STRING_LENGTH = 500;

    private const int MAX_ITEMS = 50;

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function sanitize(array $data, int $depth = 0): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return ['…' => 'max depth reached'];
        }

        $clean = [];
        $count = 0;

        foreach ($data as $key => $value) {
            if (++$count > self::MAX_ITEMS) {
                $clean['…'] = 'truncated';

                break;
            }

            if (is_string($key) && self::isSensitiveKey($key)) {
                $clean[$key] = self::REDACTED;

                continue;
            }

            $clean[$key] = match (true) {
                is_array($value) => self::sanitize($value, $depth + 1),
                is_string($value) => mb_strlen($value) > self::MAX_STRING_LENGTH
                    ? mb_substr($value, 0, self::MAX_STRING_LENGTH).'…'
                    : $value,
                is_scalar($value), $value === null => $value,
                $value instanceof \BackedEnum => $value->value,
                $value instanceof \DateTimeInterface => $value->format(DATE_ATOM),
                $value instanceof \Stringable => (string) $value,
                default => '['.get_debug_type($value).']',
            };
        }

        return $clean;
    }

    public static function isSensitiveKey(string $key): bool
    {
        return preg_match(self::SENSITIVE_KEY_PATTERN, $key) === 1;
    }
}
