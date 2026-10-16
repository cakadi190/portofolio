<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChannelPlatform;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProgrammerChannelRequest;
use App\Models\ProgrammerChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgrammerChannelController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/programmer-channels/index', [
            'channels' => Inertia::scroll($this->paginateTable(
                ProgrammerChannel::query()->orderByDesc('is_featured')->orderBy('name'),
                $request,
                ['name', 'description'],
                ['name', 'platform', 'is_featured'],
            )),
            'filters' => $this->tableFilters($request, ['name', 'platform', 'is_featured']),
            'platforms' => ChannelPlatform::options(),
        ]);
    }

    public function store(ProgrammerChannelRequest $request): RedirectResponse
    {
        ProgrammerChannel::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.channel_berhasil_ditambahkan')]);

        return to_route('admin.programmer-channels.index');
    }

    public function update(ProgrammerChannelRequest $request, ProgrammerChannel $programmerChannel): RedirectResponse
    {
        $programmerChannel->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.channel_berhasil_diperbarui')]);

        return to_route('admin.programmer-channels.index');
    }

    public function destroy(ProgrammerChannel $programmerChannel): RedirectResponse
    {
        $programmerChannel->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.channel_berhasil_dihapus')]);

        return to_route('admin.programmer-channels.index');
    }
}
