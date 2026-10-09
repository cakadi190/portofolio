<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\SpeakingEngagement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CalendarEventController extends Controller
{
    /**
     * Events of one month for the navbar calendar: published posts for every
     * staff member, plus speaking engagements for admins.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);

        $start = Carbon::createFromFormat('Y-m-d', ($validated['month'] ?? now()->format('Y-m')).'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $events = Post::where('is_published', true)
            ->whereBetween('published_at', [$start, $end])
            ->get(['id', 'title', 'published_at'])
            ->map(fn (Post $post): array => [
                'id' => "post-{$post->id}",
                'type' => 'post',
                'title' => $post->title,
                'starts_at' => $post->published_at->format('Y-m-d H:i'),
                'ends_at' => null,
                'subtitle' => 'Artikel terbit',
            ]);

        if ($request->user()->isAdmin()) {
            $events = $events->concat(
                SpeakingEngagement::where('starts_at', '<=', $end)
                    ->where(fn ($query) => $query
                        ->where('starts_at', '>=', $start)
                        ->orWhere('ends_at', '>=', $start))
                    ->get()
                    ->map(fn (SpeakingEngagement $engagement): array => [
                        'id' => "speaking-{$engagement->id}",
                        'type' => 'speaking',
                        'title' => $engagement->title,
                        'starts_at' => $engagement->starts_at->format('Y-m-d H:i'),
                        'ends_at' => $engagement->ends_at?->format('Y-m-d H:i'),
                        'subtitle' => $engagement->role->label().' · '.$engagement->organizer,
                    ]),
            );
        }

        return response()->json([
            'events' => $events->sortBy('starts_at')->values(),
            'upcoming' => $this->upcoming($request),
        ]);
    }

    /**
     * The nearest events from now on, regardless of the month on screen.
     *
     * @return Collection<int, array<string, string|null>>
     */
    private function upcoming(Request $request): Collection
    {
        $now = now();

        $events = Post::where('is_published', true)
            ->where('published_at', '>=', $now)
            ->orderBy('published_at')
            ->limit(5)
            ->get(['id', 'title', 'published_at'])
            ->map(fn (Post $post): array => [
                'id' => "post-{$post->id}",
                'type' => 'post',
                'title' => $post->title,
                'starts_at' => $post->published_at->format('Y-m-d H:i'),
                'ends_at' => null,
                'subtitle' => 'Artikel terbit',
            ]);

        if ($request->user()->isAdmin()) {
            $events = $events->concat(
                SpeakingEngagement::where('starts_at', '>=', $now->format('Y-m-d H:i:s'))
                    ->orderBy('starts_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (SpeakingEngagement $engagement): array => [
                        'id' => "speaking-{$engagement->id}",
                        'type' => 'speaking',
                        'title' => $engagement->title,
                        'starts_at' => $engagement->starts_at->format('Y-m-d H:i'),
                        'ends_at' => $engagement->ends_at?->format('Y-m-d H:i'),
                        'subtitle' => $engagement->role->label().' · '.$engagement->organizer,
                    ]),
            );
        }

        return $events->sortBy('starts_at')->take(5)->values();
    }
}
