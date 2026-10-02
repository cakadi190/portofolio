<?php

use App\Models\Media;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SystemSettingService;
use Illuminate\Support\Facades\DB;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.system-settings.index'))->assertRedirect(route('login'));
    $this->put(route('admin.system-settings.update'))->assertRedirect(route('login'));
});

test('the settings page lists groups and decrypted values', function () {
    SystemSetting::factory()->create(['key' => 'contact_email', 'value' => 'halo@example.com']);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.system-settings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/system-settings/index')
            ->has('groups', 4)
            ->where('values.contact_email', 'halo@example.com'));
});

test('settings are created and updated in bulk with encrypted values', function () {
    SystemSetting::factory()->create(['key' => 'contact_email', 'value' => 'lama@example.com']);

    $this->actingAs(User::factory()->create())
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

    $this->actingAs(User::factory()->create())
        ->put(route('admin.system-settings.update'), ['contact_email' => 'baru@example.com']);

    expect($service->get('contact_email'))->toBe('baru@example.com');
});

test('values are validated by field type', function () {
    $this->actingAs(User::factory()->create())
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
    $this->actingAs(User::factory()->create())
        ->put(route('admin.system-settings.update'), ['contact_email' => 'a@example.com', 'rogue_key' => 'x']);

    $this->assertDatabaseMissing('system_settings', ['key' => 'rogue_key']);
});

test('seo settings are created with a media library image and validated formats', function () {
    $media = Media::factory()->create();

    $this->actingAs(User::factory()->create())
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
    $this->actingAs(User::factory()->create())
        ->put(route('admin.system-settings.update'), [
            'seo_image' => 'media/tidak-ada.webp',
            'google_analytics_id' => 'bukan-id',
            'facebook_app_id' => 'abc',
        ])
        ->assertSessionHasErrors(['seo_image', 'google_analytics_id', 'facebook_app_id']);
});
