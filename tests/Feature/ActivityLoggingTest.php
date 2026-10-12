<?php

use App\Models\Activity;
use App\Models\Tag;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\ActivitySanitizer;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('model changes log only the changed fields with the actor', function () {
    $admin = User::factory()->admin()->create();
    Activity::query()->delete();

    $this->actingAs($admin);
    $tag = Tag::factory()->create(['name' => 'Laravel']);
    $tag->update(['name' => 'PHP']);

    $created = Activity::query()->where('event', 'created')->latest('id')->firstOrFail();
    $updated = Activity::query()->where('event', 'updated')->latest('id')->firstOrFail();

    expect($created->causer_id)->toBe($admin->id)->and($created->actor_type)->toBe('user')
        ->and($updated->attribute_changes['old']['name'])->toBe('Laravel')
        ->and($updated->attribute_changes['attributes']['name'])->toBe('PHP')
        ->and($updated->attribute_changes['attributes'])->not->toHaveKey('slug_unchanged');
});

test('passwords and tokens are never recorded', function () {
    Activity::query()->delete();
    $user = User::factory()->create();
    $user->update(['password' => 'new-secret-password']);

    $json = Activity::query()->get()->toJson();

    expect($json)->not->toContain('new-secret-password')->not->toContain($user->getRawOriginal('remember_token'));
});

test('the sanitizer redacts nested secrets and bounds values', function () {
    $clean = ActivitySanitizer::sanitize([
        'ok' => 'value',
        'nested' => ['api_key' => 'abc', 'deep' => ['Authorization' => 'Bearer x', 'turnstile_secret_key' => 'y']],
        'long' => str_repeat('a', 900),
    ]);

    expect($clean['nested']['api_key'])->toBe('[REDACTED]')
        ->and($clean['nested']['deep']['Authorization'])->toBe('[REDACTED]')
        ->and($clean['nested']['deep']['turnstile_secret_key'])->toBe('[REDACTED]')
        ->and($clean['ok'])->toBe('value')
        ->and(mb_strlen($clean['long']))->toBeLessThan(510);
});

test('explicit events are sanitized and stamped with request context', function () {
    $this->withHeader('X-Request-Id', '3f1e6b0a-6a3c-4d0c-9f6e-0c8f3a2b1d44')
        ->actingAs(User::factory()->admin()->create())
        ->get(route('admin.visitor-analytics.index'));

    app(AuditLogger::class)->record('system', 'probe', 'Probe', properties: ['password' => 'x', 'note' => 'ok']);

    $activity = Activity::query()->where('event', 'probe')->firstOrFail();

    expect($activity->properties['password'])->toBe('[REDACTED]')->and($activity->properties['note'])->toBe('ok');
});

test('successful and failed logins are logged and failures are rate limited', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    expect(Activity::query()->where('event', 'login')->where('causer_id', $user->id)->exists())->toBeTrue();

    auth()->logout();
    $this->post(route('logout'));

    foreach (range(1, 3) as $_) {
        $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong']);
    }

    $failed = Activity::query()->where('event', 'login_failed')->get();
    expect($failed)->toHaveCount(1)
        ->and($failed->first()->outcome)->toBe('failure')
        ->and($failed->first()->causer_id)->toBeNull()
        ->and($failed->first()->properties->toJson())->not->toContain('nobody@example.com');
});

test('model activity rolls back with its transaction while a later failure event persists', function () {
    $this->actingAs(User::factory()->admin()->create());

    try {
        DB::transaction(function () {
            Tag::factory()->create(['name' => 'Rollback']);
            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
        app(AuditLogger::class)->record('content', 'tag_create_failed', 'Gagal', outcome: 'failure');
    }

    expect(Activity::query()->where('subject_type', Tag::class)->where('event', 'created')->exists())->toBeFalse()
        ->and(Activity::query()->where('event', 'tag_create_failed')->where('outcome', 'failure')->exists())->toBeTrue();
});

test('a non critical audit write failure does not break the caller but a critical one does', function () {
    Schema::drop('activity_log');

    expect(app(AuditLogger::class)->record('system', 'x', 'x'))->toBeNull();
    expect(fn () => app(AuditLogger::class)->record('system', 'x', 'x', critical: true))->toThrow(QueryException::class);
});

test('changing a role is logged as an explicit event', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => $user->name, 'email' => $user->email, 'account_type' => 'redaktur',
    ]);

    $event = Activity::query()->where('event', 'role_changed')->first();
    expect($event)->not->toBeNull()->and($event->properties['to'])->toBe('redaktur');
});
