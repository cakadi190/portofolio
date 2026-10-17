<?php

use App\Enums\SystemSettingGroup;
use App\Jobs\SyncMediaStorageJob;
use App\Models\Media;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\MediaStorage;
use App\Services\SystemSettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.system-settings.index'))->assertRedirect(route('login'));
    $this->put(route('admin.system-settings.update'))->assertRedirect(route('login'));
});

test('the settings page lists groups and decrypted values', function () {
    SystemSetting::factory()->create(['key' => 'contact_email', 'value' => 'halo@example.com']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.system-settings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/system-settings/index')
            ->has('groups', 9)
            ->where('values.contact_email', 'halo@example.com'));
});

test('settings are created and updated in bulk with encrypted values', function () {
    SystemSetting::factory()->create(['key' => 'contact_email', 'value' => 'lama@example.com']);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.system-settings.update'), [
            'contact_email' => 'baru@example.com',
            'social_facebook' => 'https://facebook.com/cakadi190',
        ])
        ->assertRedirect(route('admin.system-settings.index'));

    expect(SystemSetting::query()->where('key', 'contact_email')->firstOrFail()->value)->toBe('baru@example.com')
        ->and(SystemSetting::query()->where('key', 'social_facebook')->firstOrFail()->value)->toBe('https://facebook.com/cakadi190')
        ->and(DB::table('system_settings')->where('key', 'contact_email')->value('value'))->not->toBe('baru@example.com');
});

test('the service cache is refreshed after saving', function () {
    $service = app(SystemSettingService::class);
    SystemSetting::factory()->create(['key' => 'contact_email', 'value' => 'lama@example.com']);
    expect($service->get('contact_email'))->toBe('lama@example.com');

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.system-settings.update'), ['contact_email' => 'baru@example.com']);

    expect($service->get('contact_email'))->toBe('baru@example.com');
});

test('values are validated by field type', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.system-settings.update'), [
            'contact_email' => 'bukan-email',
            'contact_whatsapp' => 'abc',
            'social_twitter' => 'javascript:alert(1)',
            'contact_recipient_email' => 'bukan-email',
        ])
        ->assertSessionHasErrors(['contact_email', 'contact_whatsapp', 'social_twitter', 'contact_recipient_email']);

    expect(SystemSetting::query()->count())->toBe(0);
});

test('unmanaged keys are ignored', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.system-settings.update'), ['contact_email' => 'a@example.com', 'rogue_key' => 'x']);

    $this->assertDatabaseMissing('system_settings', ['key' => 'rogue_key']);
});

test('seo settings are created with a media library image and validated formats', function () {
    $media = Media::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.system-settings.update'), [
            'seo_image' => $media->path,
            'google_analytics_id' => 'G-DY3NMX1ZWY',
            'facebook_app_id' => '123456',
            'yandex_site_verification' => 'yandex-token',
        ])
        ->assertSessionHasNoErrors();

    expect(SystemSetting::query()->where('key', 'seo_image')->value('value'))->toBe($media->path)
        ->and(SystemSetting::query()->where('key', 'google_analytics_id')->value('value'))->toBe('G-DY3NMX1ZWY');
});

test('seo settings reject malformed ids and unknown media', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.system-settings.update'), [
            'seo_image' => 'media/tidak-ada.webp',
            'google_analytics_id' => 'bukan-id',
            'facebook_app_id' => 'abc',
        ])
        ->assertSessionHasErrors(['seo_image', 'google_analytics_id', 'facebook_app_id']);
});

test('toggles that are on by default declare it so an unsaved form does not switch them off', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.system-settings.index'))
        ->assertInertia(function ($page) {
            $fields = collect($page->toArray()['props']['groups'])->pluck('fields')->flatten(1)->keyBy('key');

            expect($fields['visitor_store_ip']['default'])->toBeTrue()
                ->and($fields['visitor_tracking_enabled']['default'])->toBeTrue()
                ->and($fields['ipinfo_enabled'])->not->toHaveKey('default');
        });
});

test('the monitoring tab is labelled Sentry', function () {
    expect(SystemSettingGroup::Monitoring->label())->toBe('Sentry');
});

test('selecting a bucket needs its name and credentials', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.system-settings.update'), ['storage_driver' => 's3'])
        ->assertSessionHasErrors(['storage_s3_bucket', 'storage_s3_access_key', 'storage_s3_secret_key']);
});

test('a bucket that answers is kept and its credentials are stored encrypted', function () {
    Storage::fake('media_s3');

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.system-settings.update'), [
            'storage_driver' => 's3', 'storage_s3_bucket' => 'catatan', 'storage_s3_access_key' => 'AK', 'storage_s3_secret_key' => 'SK',
        ])
        ->assertRedirect();

    expect(SystemSetting::query()->where('key', 'storage_driver')->value('value'))->toBe('s3')
        ->and(app(MediaStorage::class)->activeDisk())->toBe('media_s3');
    expect(DB::table('system_settings')->where('key', 'storage_s3_secret_key')->value('value'))->not->toBe('SK');
});

test('a bucket that cannot be reached is reverted to local storage', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.system-settings.update'), [
            'storage_driver' => 's3', 'storage_s3_bucket' => 'catatan', 'storage_s3_access_key' => 'AK', 'storage_s3_secret_key' => 'SK',
            'storage_s3_endpoint' => 'http://127.0.0.1:1', 'storage_s3_path_style' => '1',
        ])
        ->assertRedirect();

    expect(SystemSetting::query()->where('key', 'storage_driver')->value('value'))->toBe('local')
        ->and(app(MediaStorage::class)->activeDisk())->toBe('public');
});

test('the storage connection can be tested from the settings page', function () {
    Storage::fake('media_s3');
    foreach (['storage_s3_bucket' => 'b', 'storage_s3_access_key' => 'AK', 'storage_s3_secret_key' => 'SK'] as $key => $value) {
        SystemSetting::query()->create(['key' => $key, 'value' => $value, 'is_active' => true]);
    }

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.system-settings.test'), ['group' => 'storage'])
        ->assertRedirect();

    expect(app(MediaStorage::class)->probe()['ok'])->toBeTrue();
});

test('switching to a working bucket queues the copy of existing media', function () {
    Queue::fake();
    Storage::fake('media_s3');

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.system-settings.update'), [
            'storage_driver' => 's3', 'storage_s3_bucket' => 'catatan', 'storage_s3_access_key' => 'AK', 'storage_s3_secret_key' => 'SK',
        ]);

    Queue::assertPushed(SyncMediaStorageJob::class);
});

test('a failed bucket does not queue the copy', function () {
    Queue::fake();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.system-settings.update'), [
            'storage_driver' => 's3', 'storage_s3_bucket' => 'b', 'storage_s3_access_key' => 'AK', 'storage_s3_secret_key' => 'SK',
            'storage_s3_endpoint' => 'http://127.0.0.1:1', 'storage_s3_path_style' => '1',
        ]);

    Queue::assertNotPushed(SyncMediaStorageJob::class);
});
