<?php

use App\Models\Activity;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SystemSettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function admin(): User
{
    return User::factory()->admin()->create();
}

test('secrets are encrypted at rest and never sent back to the browser', function () {
    $this->actingAs(admin())->put(route('admin.system-settings.update'), ['ipinfo_token' => 'tok_super_secret', 'ipinfo_enabled' => '1'])->assertSessionHasNoErrors();

    expect(DB::table('system_settings')->where('key', 'ipinfo_token')->value('value'))->not->toContain('tok_super_secret');

    $response = $this->actingAs(admin())->get(route('admin.system-settings.index'));
    expect($response->getContent())->not->toContain('tok_super_secret');
    $response->assertInertia(fn ($page) => $page->missing('values.ipinfo_token')->where('secretsSet', fn ($set) => collect($set)->contains('ipinfo_token')));
});

test('submitting a blank secret keeps it, rotation replaces it and the clear flag removes it', function () {
    $user = admin();
    $this->actingAs($user)->put(route('admin.system-settings.update'), ['ipinfo_token' => 'first']);
    $this->actingAs($user)->put(route('admin.system-settings.update'), ['ipinfo_token' => '', 'ipinfo_cache_ttl_days' => '10']);
    expect(SystemSetting::query()->where('key', 'ipinfo_token')->value('value'))->toBe('first');

    $this->actingAs($user)->put(route('admin.system-settings.update'), ['ipinfo_token' => 'second']);
    expect(SystemSetting::query()->where('key', 'ipinfo_token')->value('value'))->toBe('second');

    $this->actingAs($user)->put(route('admin.system-settings.update'), ['ipinfo_token' => '', 'ipinfo_token__clear' => '1']);
    expect(SystemSetting::query()->where('key', 'ipinfo_token')->value('value'))->toBeNull();
});

test('setting changes are audited by key without values', function () {
    $this->actingAs(admin())->put(route('admin.system-settings.update'), ['ipinfo_token' => 'tok_audit_secret', 'contact_email' => 'a@example.com']);

    $activity = Activity::query()->where('event', 'settings_updated')->firstOrFail();

    expect($activity->properties['masked_fields_changed'])->toBe(['ipinfo_token'])
        ->and($activity->toJson())->not->toContain('tok_audit_secret')->not->toContain('a@example.com');
});

test('integration settings are validated and defaulted', function () {
    $this->actingAs(admin())->put(route('admin.system-settings.update'), [
        'ipinfo_enabled' => 'maybe', 'ipinfo_cache_ttl_days' => '9999', 'visitor_bot_policy' => 'nope',
    ])->assertSessionHasErrors(['ipinfo_enabled', 'ipinfo_cache_ttl_days', 'visitor_bot_policy']);

    $settings = app(SystemSettingService::class);
    expect($settings->int('visitor_session_minutes', 30))->toBe(30)->and($settings->bool('ipinfo_enabled'))->toBeFalse();
});

test('non admins cannot read, change or test integration settings', function () {
    foreach ([User::factory()->redaktur()->create(), User::factory()->create()] as $user) {
        $this->actingAs($user)->get(route('admin.system-settings.index'))->assertForbidden();
        $this->actingAs($user)->put(route('admin.system-settings.update'), ['ipinfo_token' => 'x'])->assertForbidden();
        $this->actingAs($user)->post(route('admin.system-settings.test'))->assertForbidden();
    }
});

test('the connection test reports without exposing the token', function () {
    $this->actingAs(admin())->put(route('admin.system-settings.update'), ['ipinfo_token' => 'tok_test_secret', 'ipinfo_enabled' => '1']);
    Http::fake(['api.ipinfo.io/*' => Http::response([], 401)]);

    $this->actingAs(admin())->post(route('admin.system-settings.test'))->assertRedirect();

    Http::assertSent(fn ($r) => ! str_contains($r->url(), 'tok_test_secret'));
});
