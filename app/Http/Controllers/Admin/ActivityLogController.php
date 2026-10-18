<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivityLogFilterRequest;
use App\Models\Activity;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\ActivitySanitizer;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Read-only audit trail viewer. Admin only (route gate `manage-site`, plus the
 * form request check for defence in depth); exports are separately bounded.
 */
class ActivityLogController extends Controller
{
    private const int EXPORT_LIMIT = 5000;

    public function index(ActivityLogFilterRequest $request): Response
    {
        $sort = $request->validated('sort', 'created_at');
        $direction = $request->validated('direction', 'desc');
        $perPage = (int) $request->validated('per_page', 25);

        $activities = Activity::query()
            ->with(['causer:id,name,email'])
            ->filteredBy($request->filters())
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Activity $activity): array => $this->row($activity));

        return Inertia::render('admin/activity-logs/index', [
            'activities' => $activities,
            'totalActivities' => $activities->total(),
            'filters' => [...$request->filters(), 'sort' => $sort, 'direction' => $direction, 'per_page' => $perPage],
            'options' => [
                'log_names' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name')->filter()->values(),
                'events' => Activity::query()->distinct()->orderBy('event')->pluck('event')->filter()->values(),
                'subject_types' => Activity::query()->whereNotNull('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type')->values(),
                'users' => User::query()->where('account_type', '!=', 'user')->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function show(Activity $activity): Response
    {
        $activity->load(['causer:id,name,email', 'subject']);

        return Inertia::render('admin/activity-logs/show', [
            'activity' => [
                ...$this->row($activity),
                'request_id' => $activity->request_id,
                'http_method' => $activity->http_method,
                'http_status' => $activity->http_status,
                'user_agent' => $activity->user_agent,
                'subject_exists' => $activity->subject !== null,
                'properties' => ActivitySanitizer::sanitize($activity->properties?->toArray() ?? []),
                'changes' => ActivitySanitizer::sanitize($activity->attribute_changes?->toArray() ?? []),
                'related_count' => $activity->request_id === null ? 0 : Activity::query()->where('request_id', $activity->request_id)->whereKeyNot($activity->getKey())->count(),
            ],
        ]);
    }

    public function export(ActivityLogFilterRequest $request, AuditLogger $audit): StreamedResponse
    {
        $audit->record('system', 'activity_exported', 'Log aktivitas diekspor', properties: ['filters' => $request->filters(), 'limit' => self::EXPORT_LIMIT], causer: $request->user());

        $query = Activity::query()->with('causer:id,name')->filteredBy($request->filters())->orderByDesc('created_at')->orderByDesc('id')->limit(self::EXPORT_LIMIT);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'waktu', 'aktor', 'log', 'event', 'deskripsi', 'subjek', 'hasil', 'ip']);

            $query->lazyById(500)->each(function (Activity $a) use ($out): void {
                fputcsv($out, array_map($this->csvSafe(...), [
                    $a->id, $a->created_at?->toIso8601String(), $a->causer?->name ?? $a->actor_type, $a->log_name, $a->event,
                    $a->description, $a->subject_type ? class_basename($a->subject_type).'#'.$a->subject_id : '', $a->outcome, $a->ip_address,
                ]));
            });

            fclose($out);
        }, 'activity-log-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'created_at' => $activity->created_at?->toIso8601String(),
            'log_name' => $activity->log_name,
            'event' => $activity->event,
            'description' => $activity->description,
            'outcome' => $activity->outcome,
            'actor_type' => $activity->actor_type,
            'causer' => $activity->causer ? ['id' => $activity->causer->id, 'name' => $activity->causer->name, 'email' => $activity->causer->email] : null,
            'subject_type' => $activity->subject_type ? class_basename($activity->subject_type) : null,
            'subject_id' => $activity->subject_id,
            'route_name' => $activity->route_name,
            'ip_address' => $activity->ip_address,
        ];
    }

    private function csvSafe(mixed $value): string
    {
        $text = (string) $value;

        return $text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$text : $text;
    }
}
