<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\ActivityLogger;
use Throwable;

/**
 * Record explicit domain events (logins, setting changes, exceptional actions)
 * that cannot be inferred from Eloquent model events.
 *
 * Failure policy: a write failure never breaks the request, it is reported to
 * the application log, unless `$critical` is true, in which case the exception
 * propagates so the surrounding operation can abort. Model-level changes use
 * the surrounding transaction, so a rolled-back operation leaves no audit row;
 * record failure or denial events after the rollback.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties  Sanitized recursively on save.
     */
    public function record(
        string $logName,
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        string $outcome = Activity::OUTCOME_SUCCESS,
        ?Model $causer = null,
        bool $critical = false,
    ): ?Activity {
        try {
            $logger = app(ActivityLogger::class)->useLog($logName)->event($event)->withProperties($properties);

            if ($subject instanceof Model) {
                $logger->performedOn($subject);
            }

            if ($causer instanceof Model) {
                $logger->causedBy($causer);
            }

            $logger->tap(function (Activity $activity) use ($outcome): void {
                $activity->outcome = $outcome;
            });

            $activity = $logger->log($description);

            return $activity instanceof Activity ? $activity : null;
        } catch (Throwable $exception) {
            if ($critical) {
                throw $exception;
            }

            report($exception);

            return null;
        }
    }
}
