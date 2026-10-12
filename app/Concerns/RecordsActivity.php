<?php

namespace App\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Log field-level changes of a model to the audit trail.
 *
 * Only changed attributes are recorded (never a full snapshot) and secrets are
 * excluded by default. Models can set `$activityLogName` and extend
 * `$activityExcept` to tune the output.
 */
trait RecordsActivity
{
    use LogsActivity;

    /**
     * @var list<string>
     */
    private static array $alwaysExceptFromActivity = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
        'updated_at', 'created_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept([...self::$alwaysExceptFromActivity, ...($this->activityExcept ?? [])])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName($this->activityLogName ?? 'content')
            ->setDescriptionForEvent(fn (string $event): string => class_basename($this)." {$event}");
    }
}
