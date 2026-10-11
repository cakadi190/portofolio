<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClarificationVerdict;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClarificationRequest;
use App\Models\Clarification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClarificationController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/clarifications/index', [
            'clarifications' => $this->paginateTable(
                Clarification::query()->orderByDesc('created_at'),
                $request,
                ['title', 'claim'],
                ['title', 'verdict', 'is_published', 'published_at'],
            )->through(fn (Clarification $clarification): array => [
                'id' => $clarification->id,
                'title' => $clarification->title,
                'slug' => $clarification->slug,
                'verdict_label' => $clarification->verdict->label(),
                'verdict_color' => $clarification->verdict->color(),
                'is_published' => $clarification->is_published,
                'published_at' => $clarification->published_at,
            ]),
            'filters' => $this->tableFilters($request, ['title', 'verdict', 'is_published', 'published_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/clarifications/form', [
            'clarification' => null,
            'verdicts' => ClarificationVerdict::options(),
        ]);
    }

    public function edit(Clarification $clarification): Response
    {
        return Inertia::render('admin/clarifications/form', [
            'clarification' => $clarification,
            'verdicts' => ClarificationVerdict::options(),
        ]);
    }

    public function store(ClarificationRequest $request): RedirectResponse
    {
        $clarification = Clarification::query()->create($this->payload($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Klarifikasi berhasil ditambahkan.']);

        return to_route('admin.clarifications.edit', $clarification);
    }

    public function update(ClarificationRequest $request, Clarification $clarification): RedirectResponse
    {
        $clarification->update($this->payload($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Klarifikasi berhasil diperbarui.']);

        return to_route('admin.clarifications.edit', $clarification);
    }

    public function destroy(Clarification $clarification): RedirectResponse
    {
        $clarification->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Klarifikasi berhasil dihapus.']);

        return to_route('admin.clarifications.index');
    }

    /**
     * Validated data; publishing without a date publishes right away.
     *
     * @return array<string, mixed>
     */
    private function payload(ClarificationRequest $request): array
    {
        $data = $request->validated();

        if ($request->boolean('is_published') && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
