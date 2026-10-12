<?php

namespace App\Models;

use App\Http\Middleware\AssignRequestId;
use App\Support\ActivitySanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity as BaseActivity;

/**
 * An audit trail entry. Request context (actor type, correlation id, route, IP
 * address, user agent) is stamped on creation and every payload is sanitized,
 * so callers can never leak secrets by passing raw request data.
 *
 * @property string $actor_type user|system|guest
 * @property string $outcome success|failure
 * @property string|null $request_id
 * @property string|null $http_method
 * @property string|null $route_name
 * @property int|null $http_status
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class Activity extends BaseActivity
{
    public const string OUTCOME_SUCCESS = 'success';

    public const string OUTCOME_FAILURE = 'failure';

    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();
            $routeName = $request?->route()?->getName();

            $activity->actor_type ??= match (true) {
                $activity->causer_id !== null => 'user',
                $request !== null && $activity->event !== null && $request->hasSession() => 'guest',
                default => 'system',
            };
            $activity->outcome ??= self::OUTCOME_SUCCESS;
            $activity->request_id ??= $request?->attributes->get(AssignRequestId::ATTRIBUTE);
            $activity->http_method ??= $request?->method();
            $activity->route_name ??= $routeName === null ? null : Str::limit($routeName, 120, '');
            $activity->ip_address ??= $request?->ip();
            $activity->user_agent ??= $request?->userAgent() === null ? null : Str::limit($request->userAgent(), 500, '');

            $activity->properties = collect(ActivitySanitizer::sanitize($activity->properties?->toArray() ?? []));
            $activity->attribute_changes = collect(ActivitySanitizer::sanitize($activity->attribute_changes?->toArray() ?? []));
        });
    }

    /**
     * Restrict the query to entries matching the admin viewer filters.
     *
     * @param  Builder<static>  $query
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilteredBy(Builder $query, array $filters): void
    {
        $like = fn (string $term): string => '%'.addcslashes($term, '\\%_').'%';

        $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $term) => $q->where(fn (Builder $q) => $q
                ->where('description', 'like', $like($term))
                ->orWhere('event', 'like', $like($term))
                ->orWhere('log_name', 'like', $like($term))
                ->orWhere('request_id', $term)))
            ->when($filters['log_name'] ?? null, fn (Builder $q, string $v) => $q->where('log_name', $v))
            ->when($filters['event'] ?? null, fn (Builder $q, string $v) => $q->where('event', $v))
            ->when($filters['outcome'] ?? null, fn (Builder $q, string $v) => $q->where('outcome', $v))
            ->when($filters['causer_id'] ?? null, fn (Builder $q, int $v) => $q->where('causer_type', (new User)->getMorphClass())->where('causer_id', $v))
            ->when($filters['subject_type'] ?? null, fn (Builder $q, string $v) => $q->where('subject_type', $v))
            ->when($filters['subject_id'] ?? null, fn (Builder $q, int $v) => $q->where('subject_id', $v))
            ->when($filters['ip_address'] ?? null, fn (Builder $q, string $v) => $q->where('ip_address', $v))
            ->when($filters['from'] ?? null, fn (Builder $q, string $v) => $q->where('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn (Builder $q, string $v) => $q->where('created_at', '<=', $v));
    }
}
