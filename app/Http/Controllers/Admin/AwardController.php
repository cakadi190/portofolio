<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AwardRequest;
use App\Models\Award;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AwardController extends Controller
{
    use HandlesUploads;

    public function index(): Response
    {
        return Inertia::render('admin/awards/index', [
            'awards' => Award::query()->orderByDesc('awarded_at')->orderByDesc('year')->paginate(20),
        ]);
    }

    public function store(AwardRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('icon');

        if ($request->hasFile('icon')) {
            $data['icon'] = $this->storeUpload($request->file('icon'), 'awards');
        }

        Award::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Penghargaan berhasil ditambahkan.']);

        return to_route('admin.awards.index');
    }

    public function update(AwardRequest $request, Award $award): RedirectResponse
    {
        $data = $request->safe()->except('icon');

        if ($request->hasFile('icon')) {
            $this->deleteUpload($award->icon);
            $data['icon'] = $this->storeUpload($request->file('icon'), 'awards');
        }

        $award->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Penghargaan berhasil diperbarui.']);

        return to_route('admin.awards.index');
    }

    public function destroy(Award $award): RedirectResponse
    {
        $this->deleteUpload($award->icon);
        $award->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Penghargaan berhasil dihapus.']);

        return to_route('admin.awards.index');
    }
}
