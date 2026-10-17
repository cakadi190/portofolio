<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SystemSettingGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingRequest;
use App\Jobs\SyncMediaStorageJob;
use App\Models\SystemSetting;
use App\Services\AuditLogger;
use App\Services\MediaStorage;
use App\Services\SystemSettingService;
use App\Services\Visitors\IpInfoClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function __construct(
        private SystemSettingService $systemSettings,
        private IpInfoClient $ipInfo,
        private AuditLogger $audit,
        private MediaStorage $storage,
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
                'storage' => [
                    'enabled' => $this->storage->usesS3(),
                    'configured' => $this->storage->isS3Configured(),
                    'reason' => $this->storage->isS3Configured() ? null : __('server.storage_probe_incomplete'),
                ],
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
        $this->storage->forgetUnsynced();

        $toast = ['type' => 'success', 'message' => __('flash.pengaturan_berhasil_disimpan')];

        if (in_array('storage_driver', $changed, true) && $request->input('storage_driver') === 's3') {
            $probe = $this->storage->probe();

            if ($probe['ok']) {
                SyncMediaStorageJob::dispatch();
                $toast['message'] .= ' '.__('server.storage_switched');
            } else {
                SystemSetting::query()->where('key', 'storage_driver')->first()?->update(['value' => 'local']);
                $this->systemSettings->forget();
                $this->storage->forgetUnsynced();
                $toast = ['type' => 'error', 'message' => __('server.storage_reverted', ['message' => $probe['message']])];
            }
        }

        if (in_array('storage_driver', $changed, true) && $request->input('storage_driver') === 'local') {
            SyncMediaStorageJob::dispatch();
            $toast['message'] .= ' '.__('server.storage_switched_local');
        }

        Inertia::flash('toast', $toast);

        return to_route('admin.system-settings.index');
    }

    public function testConnection(Request $request): RedirectResponse
    {
        $result = $request->input('group') === SystemSettingGroup::Storage->value
            ? $this->storage->probe()
            : $this->ipInfo->test();

        Inertia::flash('toast', ['type' => $result['ok'] ? 'success' : 'error', 'message' => $result['message']]);

        return back();
    }
}
