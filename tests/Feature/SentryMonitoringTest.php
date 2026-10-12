<?php

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Monitoring\SentryConfigurator;
use App\Services\Monitoring\SentrySettings;
use App\Services\SystemSettingService;
use App\Support\Sentry\PiiRedactor;
use App\Support\Sentry\SentryEventScrubber;
use Sentry\Event;
use Sentry\UserDataBag;

function setSentry(array $values): void
{
    foreach ($values as $key => $value) {
        SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'is_active' => true]);
    }

    app(SystemSettingService::class)->forget();
}

test('sentry stays off until enabled with a dsn', function () {
    setSentry(['sentry_backend_dsn' => 'https://k@o1.ingest.sentry.io/1']);

    expect(app(SentrySettings::class)->backendDsn())->toBeNull()
        ->and(app(SentrySettings::class)->frontendConfig())->toBeNull();
});

test('settings are copied into runtime config without default pii', function () {
    setSentry([
        'sentry_enabled' => '1', 'sentry_backend_dsn' => 'https://k@o1.ingest.sentry.io/1',
        'sentry_traces_sample_percent' => '25', 'sentry_enable_logs' => '1', 'sentry_environment' => 'staging',
    ]);

    (new SentryConfigurator(app(SentrySettings::class), config()))->apply();

    expect(config('sentry.dsn'))->toBe('https://k@o1.ingest.sentry.io/1')
        ->and(config('sentry.traces_sample_rate'))->toBe(0.25)
        ->and(config('sentry.environment'))->toBe('staging')
        ->and(config('sentry.send_default_pii'))->toBeFalse()
        ->and(config('logging.channels.stack.channels'))->toContain('sentry_logs');
});

test('the frontend dsn and sampling reach the page as a meta tag', function () {
    setSentry(['sentry_enabled' => '1', 'sentry_frontend_dsn' => 'https://k@o1.ingest.sentry.io/2', 'sentry_traces_sample_percent' => '50']);

    $this->get('/')->assertOk()->assertSee('name="sentry-config"', false)->assertSee('o1.ingest.sentry.io/2', false);
});

test('the frontend dsn is omitted while disabled', function () {
    $this->get('/')->assertOk()->assertDontSee('sentry-config', false);
});

test('inertia shares only a pseudonymous user reference', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->get(route('admin.system-settings.index'))->assertInertia(fn ($page) => $page
        ->where('monitoring.actor', 'admin')
        ->where('monitoring.userRef', fn ($ref) => strlen($ref) === 16 && ! str_contains($ref, (string) $user->getKey().$user->email)));
});

test('events are scrubbed of personal data', function () {
    $event = Event::createEvent();
    $event->setRequest(['url' => 'https://x.test/a?token=abc&email=a@b.com', 'cookies' => ['s' => '1'], 'data' => ['password' => 'p'], 'headers' => ['Cookie' => 'x', 'User-Agent' => 'ua']]);
    $event->setUser(UserDataBag::createFromUserIdentifier('ref')->setEmail('a@b.com')->setIpAddress('1.2.3.4'));
    $event->setMessage('Gagal kirim ke a@b.com dari 10.0.0.1');
    $event->setExtra(['password' => 'p', 'note' => '081234567890']);

    $clean = SentryEventScrubber::beforeSend($event);

    expect($clean->getRequest())->not->toHaveKeys(['cookies', 'data'])
        ->and($clean->getRequest()['headers'])->toBe(['User-Agent' => 'ua'])
        ->and(json_encode($clean->getRequest()))->not->toContain('abc')->not->toContain('a@b.com')
        ->and($clean->getUser()->getEmail())->toBeNull()
        ->and($clean->getUser()->getIpAddress())->toBeNull()
        ->and($clean->getUser()->getId())->toBe('ref')
        ->and($clean->getMessage())->toBe('Gagal kirim ke [email] dari [ip]')
        ->and($clean->getExtra())->toBe(['password' => '[Filtered]', 'note' => '[phone]']);
});

test('redactor masks national ids, jwt and bearer tokens', function () {
    expect(PiiRedactor::string('NIK 3501010101010001 Bearer abc.def eyJhbGciOi.eyJzdWIi.sig'))
        ->toBe('NIK [id-number] Bearer [token] [jwt]');
});
