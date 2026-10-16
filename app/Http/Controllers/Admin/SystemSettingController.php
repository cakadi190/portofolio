<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SystemSettingGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingRequest;
use App\Models\SystemSetting;
use App\Services\AuditLogger;
use App\Services\SystemSettingService;
use App\Services\Visitors\IpInfoClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function __construct(
        private SystemSettingService $systemSettings,
        private IpInfoClient $ipInfo,
        private AuditLogger $audit,
    ) {}

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
            'values' => SystemSetting::query()
                ->whereNotIn('key', SystemSettingGroup::secretKeys())
                ->pluck('value', 'key')
                ->all(),
            'secretsSet' => SystemSetting::query()
                ->whereIn('key', SystemSettingGroup::secretKeys())
                ->whereNotNull('value')
                ->pluck('key')
                ->all(),
            'status' => [
                'ipinfo' => $this->ipInfo->status(),
            ],
        ]);
    }

    public function update(UpdateSystemSettingRequest $request): RedirectResponse
    {
        $changed = DB::transaction(function () use ($request): array {
            $changed = [];

            foreach ($request->settingValues() as $key => $value) {
                $setting = SystemSetting::query()->firstOrNew(['key' => $key]);

                if ($setting->exists && $setting->value === $value && $setting->is_active) {
                    continue;
                }

                $setting->fill(['value' => $value, 'is_active' => true])->save();
                $changed[] = $key;
            }

            return $changed;
        });

        if ($changed !== []) {
            $secrets = SystemSettingGroup::secretKeys();

            $this->audit->record(
                logName: 'settings',
                event: 'settings_updated',
                description: 'Pengaturan sistem diperbarui',
                properties: [
                    'keys' => $changed,
                    'masked_fields_changed' => array_values(array_intersect($changed, $secrets)),
                ],
                causer: $request->user(),
            );
        }

        $this->systemSettings->forget();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.pengaturan_berhasil_disimpan')]);

        return to_route('admin.system-settings.index');
    }

    public function testConnection(): RedirectResponse
    {
        $result = $this->ipInfo->test();

        Inertia::flash('toast', ['type' => $result['ok'] ? 'success' : 'error', 'message' => $result['message']]);

        return back();
    }
}
