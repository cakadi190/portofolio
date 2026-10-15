<?php

namespace App\Http\Controllers;

use App\Enums\CommunityPlatform;
use App\Models\DevCommunity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DevCommunityController extends Controller
{
    /**
     * Show the paginated list, filterable by platform.
     */
    public function index(Request $request): Response
    {
        $platform = CommunityPlatform::tryFrom((string) $request->query('platform', ''));

        $items = DevCommunity::query()
            ->when($platform, fn ($query) => $query->where('platform', $platform))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (DevCommunity $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'platform' => $item->platform->value,
                'platformLabel' => $item->platform->label(),
                'description' => $item->description,
                'url' => $item->invite_url,
                'memberCount' => $item->member_count,
                'isFeatured' => $item->is_featured,
            ]);

        return Inertia::render('sumber-daya/komunitas-dev-id', [
            'communities' => Inertia::scroll($items),
            'platforms' => CommunityPlatform::options(),
            'filters' => ['platform' => $platform?->value],
        ]);
    }
}
