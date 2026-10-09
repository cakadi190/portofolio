<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SpeakingFormat;
use App\Enums\SpeakingRole;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SpeakingEngagementRequest;
use App\Models\SpeakingEngagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpeakingEngagementController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/speaking-engagements/index', [
            'speakingEngagements' => $this->paginateTable(
                SpeakingEngagement::query()->orderByDesc('starts_at'),
                $request,
                ['title', 'organizer', 'location'],
                ['title', 'organizer', 'role', 'starts_at', 'is_published'],
            ),
            'filters' => $this->tableFilters($request, ['title', 'organizer', 'role', 'starts_at', 'is_published']),
            'roles' => SpeakingRole::options(),
            'formats' => SpeakingFormat::options(),
        ]);
    }

    public function store(SpeakingEngagementRequest $request): RedirectResponse
    {
        SpeakingEngagement::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Acara berhasil ditambahkan.']);

        return to_route('admin.speaking-engagements.index');
    }

    public function update(SpeakingEngagementRequest $request, SpeakingEngagement $speakingEngagement): RedirectResponse
    {
        $speakingEngagement->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Acara berhasil diperbarui.']);

        return to_route('admin.speaking-engagements.index');
    }

    public function destroy(SpeakingEngagement $speakingEngagement): RedirectResponse
    {
        $speakingEngagement->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Acara berhasil dihapus.']);

        return to_route('admin.speaking-engagements.index');
    }
}
