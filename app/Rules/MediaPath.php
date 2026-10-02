<?php

namespace App\Rules;

use App\Models\Media;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A stored file reference must point at a media library item. The value
 * already saved on the record is also accepted, so rows that predate the
 * library (or seeded asset paths) can still be edited.
 */
class MediaPath implements ValidationRule
{
    /**
     * @param  string|list<string>|null  $current
     */
    public function __construct(protected string|array|null $current = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array($value, (array) $this->current, true)) {
            return;
        }

        if (! Media::query()->where('path', $value)->exists()) {
            $fail('Media yang dipilih tidak ditemukan di pustaka media.');
        }
    }
}
