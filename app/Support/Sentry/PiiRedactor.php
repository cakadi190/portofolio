<?php

namespace App\Support\Sentry;

use App\Support\ActivitySanitizer;

/**
 * Strip personal data (UU PDP / GDPR) from anything bound for Sentry: values
 * under sensitive keys are filtered and identifiers embedded in free text
 * (emails, IPs, phone numbers, tokens, national IDs) are masked.
 */
final class PiiRedactor
{
    public const string FILTERED = '[Filtered]';

    private const array PATTERNS = [
        '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i' => '[email]',
        '/\bBearer\s+[A-Za-z0-9\-._~+\/]+=*/i' => 'Bearer [token]',
        '/\beyJ[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]*/' => '[jwt]',
        '/\b(?:\d{1,3}\.){3}\d{1,3}\b/' => '[ip]',
        '/\b(?:[0-9a-f]{1,4}:){4,7}[0-9a-f]{1,4}\b/i' => '[ip]',
        '/(?<!\d)(?:\+62|62|0)8\d{7,12}(?!\d)/' => '[phone]',
        '/(?<!\d)\d{16}(?!\d)/' => '[id-number]',
    ];

    public static function string(string $value): string
    {
        return (string) preg_replace(array_keys(self::PATTERNS), array_values(self::PATTERNS), $value);
    }

    public static function url(string $url): string
    {
        $parts = explode('?', $url, 2);

        if (! isset($parts[1])) {
            return self::string($url);
        }

        parse_str(explode('#', $parts[1], 2)[0], $query);

        return self::string($parts[0]).'?'.http_build_query(self::value($query));
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function value(mixed $data, int $depth = 0): mixed
    {
        if (is_string($data)) {
            return self::string($data);
        }

        if (! is_array($data) || $depth > 8) {
            return is_scalar($data) || $data === null ? $data : self::FILTERED;
        }

        $clean = [];

        foreach ($data as $key => $value) {
            $clean[$key] = is_string($key) && ActivitySanitizer::isSensitiveKey($key)
                ? self::FILTERED
                : self::value($value, $depth + 1);
        }

        return $clean;
    }
}
