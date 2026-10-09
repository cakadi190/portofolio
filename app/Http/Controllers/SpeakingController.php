<?php

namespace App\Http\Controllers;

use App\Models\SpeakingEngagement;
use Inertia\Inertia;
use Inertia\Response;

class SpeakingController extends Controller
{
    /**
     * Show upcoming and past speaking engagements.
     */
    public function index(): Response
    {
        $engagements = SpeakingEngagement::query()
            ->published()
            ->orderByDesc('starts_at')
            ->get()
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
                'isUpcoming' => ($engagement->ends_at ?? $engagement->starts_at)->isFuture(),
            ]);

        return Inertia::render('speaking/index', [
            'upcoming' => $engagements->where('isUpcoming', true)->sortBy('startsAt')->values(),
            'past' => $engagements->where('isUpcoming', false)->values(),
        ]);
    }
}
