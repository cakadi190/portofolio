<?php

namespace App\Http\Controllers;

use App\Enums\ChannelPlatform;
use App\Models\ProgrammerChannel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgrammerChannelController extends Controller
{
    /**
     * Show the paginated list, filterable by platform.
     */
    public function index(Request $request): Response
    {
        $platform = ChannelPlatform::tryFrom((string) $request->query('platform', ''));

        $items = ProgrammerChannel::query()
            ->when($platform, fn ($query) => $query->where('platform', $platform))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (ProgrammerChannel $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'platform' => $item->platform->value,
                'platformLabel' => $item->platform->label(),
                'description' => $item->description,
                'url' => $item->url,
                'isFeatured' => $item->is_featured,
            ]);

        return Inertia::render('sumber-daya/channel-programmer', [
            'channels' => Inertia::scroll($items),
            'platforms' => ChannelPlatform::options(),
            'filters' => ['platform' => $platform?->value],
        ]);
    }
}
