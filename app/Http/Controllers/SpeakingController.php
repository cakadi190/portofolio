<?php

namespace App\Http\Controllers;

use App\Enums\SpeakingFormat;
use App\Models\SpeakingEngagement;
use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class SpeakingController extends Controller
{
    /**
     * Show upcoming and past speaking engagements.
     */
    public function index(SeoService $seo): Response
    {
        $models = SpeakingEngagement::query()
            ->published()
            ->orderByDesc('starts_at')
            ->get();

        $seo->set(['json_ld' => $models->take(10)->map(fn (SpeakingEngagement $engagement): array => $this->eventSchema($engagement, $seo))->values()->all()]);

        $engagements = $models
            ->map(fn (SpeakingEngagement $engagement): array => [
                'id' => $engagement->id,
                'title' => $engagement->title,
                'organizer' => $engagement->organizer,
                'role' => $engagement->role->value,
                'roleLabel' => $engagement->role->label(),
                'format' => $engagement->format->value,
                'formatLabel' => $engagement->format->label(),
                'location' => $engagement->location,
                'startsAt' => $engagement->starts_at->format('Y-m-d H:i'),
                'endsAt' => $engagement->ends_at?->format('Y-m-d H:i'),
                'registrationUrl' => $engagement->registration_url,
                'description' => $engagement->description,
                'poster' => $engagement->poster,
                'isUpcoming' => ! $engagement->hasEnded(),
            ]);

        return Inertia::render('speaking/index', [
            'upcoming' => $engagements->where('isUpcoming', true)->sortBy('startsAt')->values(),
            'past' => $engagements->where('isUpcoming', false)->values(),
        ]);
    }

    /**
     * Schema.org Event for an engagement where the site owner speaks or mentors.
     *
     * @return array<string, mixed>
     */
    private function eventSchema(SpeakingEngagement $engagement, SeoService $seo): array
    {
        $isOnline = $engagement->format === SpeakingFormat::Online;

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $engagement->title,
            'startDate' => $engagement->starts_at->toAtomString(),
            'endDate' => $engagement->ends_at?->toAtomString(),
            'eventAttendanceMode' => match ($engagement->format) {
                SpeakingFormat::Online => 'https://schema.org/OnlineEventAttendanceMode',
                SpeakingFormat::Offline => 'https://schema.org/OfflineEventAttendanceMode',
                SpeakingFormat::Hybrid => 'https://schema.org/MixedEventAttendanceMode',
            },
            'location' => $isOnline
                ? ['@type' => 'VirtualLocation', 'url' => $engagement->registration_url ?: route('speaking.index')]
                : ($engagement->location ? ['@type' => 'Place', 'name' => $engagement->location, 'address' => $engagement->location] : null),
            'description' => str(strip_tags((string) $engagement->description))->squish()->limit(200)->toString() ?: null,
            'organizer' => ['@type' => 'Organization', 'name' => $engagement->organizer],
            'performer' => ['@type' => 'Person', 'name' => $seo->author(), 'url' => url('/')],
            'url' => route('speaking.index'),
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
