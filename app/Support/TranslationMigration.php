<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Data-only helper for migrations that turn existing plain-text columns into
 * translated ones: each value becomes `{"id": "<old value>"}`. Idempotent, so it
 * is safe to run on a database that was already converted or is still empty.
 */
class TranslationMigration
{
    /**
     * @param  list<string>  $columns
     */
    public static function wrap(string $table, array $columns): void
    {
        DB::table($table)->orderBy('id')->select(['id', ...$columns])->each(function (object $row) use ($table, $columns): void {
            $changes = [];

            foreach ($columns as $column) {
                $value = $row->{$column};

                if ($value === null || self::isTranslationMap($value)) {
                    continue;
                }

                $changes[$column] = json_encode([AppLocale::DEFAULT => $value], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if ($changes !== []) {
                DB::table($table)->where('id', $row->id)->update($changes);
            }
        });
    }

    private static function isTranslationMap(mixed $value): bool
    {
        if (! is_string($value) || ! str_starts_with($value, '{')) {
            return false;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) && $decoded !== [] && array_diff(array_keys($decoded), AppLocale::available()) === [];
    }
}
