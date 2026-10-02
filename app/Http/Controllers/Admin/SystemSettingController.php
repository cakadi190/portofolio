<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SystemSettingGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingRequest;
use App\Models\SystemSetting;
use App\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function __construct(private SystemSettingService $systemSettings) {}

    public function index(): Response
    {
        return Inertia::render('admin/system-settings/index', [
            'groups' => array_map(
                static fn (SystemSettingGroup $group): array => [
                    'value' => $group->value,
                    'label' => $group->label(),
                    'description' => $group->description(),
                    'fields' => $group->fields(),
                ],
                SystemSettingGroup::cases(),
            ),
            'values' => SystemSetting::query()->pluck('value', 'key')->all(),
        ]);
    }

    public function update(UpdateSystemSettingRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated() as $key => $value) {
                SystemSetting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'is_active' => true],
                );
            }
        });

        $this->systemSettings->forget();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengaturan berhasil disimpan.']);

        return to_route('admin.system-settings.index');
    }
}
