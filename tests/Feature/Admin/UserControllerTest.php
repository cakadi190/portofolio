<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

beforeEach(fn () => $this->withSession(['auth.password_confirmed_at' => time()]));

test('guests are redirected to the login page', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

test('a user can be created with a library avatar', function () {
    $media = Media::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.users.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'account_type' => 'user',
        'avatar' => $media->path,
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();
    expect($user->avatar)->toBe($media->path);
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

test('creating a user requires a name, email, password and account type', function () {
    $response = $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.users.store'), []);

    $response->assertSessionHasErrors(['name', 'email', 'password', 'account_type']);
});

test('updating a user without a password keeps the previous password', function () {
    $user = User::factory()->admin()->create();
    $originalPassword = $user->password;

    $response = $this->actingAs(User::factory()->admin()->create())->put(route('admin.users.update', $user), [
        'name' => 'Updated Name',
        'email' => $user->email,
        'account_type' => 'user',
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
    expect($user->password)->toBe($originalPassword);
});

test('updating a user swaps the avatar', function () {
    $user = User::factory()->create(['avatar' => 'avatars/old.png']);
    $media = Media::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())->put(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'account_type' => 'user',
        'avatar' => $media->path,
    ]);

    $response->assertRedirect(route('admin.users.index'));

    expect($user->refresh()->avatar)->toBe($media->path);
});

test('a user can be deleted', function () {
    $user = User::factory()->create(['avatar' => 'avatars/avatar.png']);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.users.destroy', $user));

    $response->assertRedirect(route('admin.users.index'));
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('an admin cannot delete their own account', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertRedirect();

    $this->assertModelExists($admin);
});

test('the last admin cannot be deleted', function () {
    $admin = User::factory()->admin()->create();
    $editor = User::factory()->redaktur()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertRedirect();

    $this->assertModelExists($admin);
    $this->assertModelExists($editor);
});

test('another admin can be deleted while a different admin remains', function () {
    $actor = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();

    $this->actingAs($actor)->delete(route('admin.users.destroy', $other))
        ->assertRedirect(route('admin.users.index'));

    $this->assertModelMissing($other);
});

test('the last admin cannot be demoted', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put(route('admin.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'account_type' => 'user',
    ])->assertRedirect();

    expect($admin->refresh()->isAdmin())->toBeTrue();
});

test('an admin can be demoted while another admin remains', function () {
    $actor = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();

    $this->actingAs($actor)->put(route('admin.users.update', $other), [
        'name' => $other->name,
        'email' => $other->email,
        'account_type' => 'redaktur',
    ])->assertRedirect(route('admin.users.index'));

    expect($other->refresh()->isAdmin())->toBeFalse();
});

test('user changes redirect to password confirmation when it has lapsed', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $payload = ['name' => 'Baru', 'email' => $user->email, 'account_type' => 'user'];

    $this->withSession(['auth.password_confirmed_at' => time() - 10_000]);

    $this->actingAs($admin)->put(route('admin.users.update', $user), $payload)->assertRedirect(route('password.confirm'));
    $this->actingAs($admin)->post(route('admin.users.store'), [...$payload, 'email' => 'baru@example.com', 'password' => 'password123'])->assertRedirect(route('password.confirm'));
    $this->actingAs($admin)->delete(route('admin.users.destroy', $user))->assertRedirect(route('password.confirm'));

    expect($user->fresh()->name)->not->toBe('Baru')
        ->and(User::where('email', 'baru@example.com')->exists())->toBeFalse();
});

test('resetting a password or changing a role signs the user out of every session', function (array $change) {
    config(['session.driver' => 'database']);
    $this->withSession(['auth.password_confirmed_at' => time()]);
    $admin = User::factory()->admin()->create();
    $user = User::factory()->redaktur()->create();
    $insert = fn (int $id, string $session) => DB::table('sessions')->insert(['id' => $session, 'user_id' => $id, 'payload' => '', 'last_activity' => time()]);
    $insert($user->id, 'victim-session');
    $insert($admin->id, 'other-admin-session');
    $tokenBefore = $user->remember_token;

    $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => $user->name, 'email' => $user->email, 'account_type' => 'redaktur', ...$change,
    ])->assertRedirect();

    expect(DB::table('sessions')->where('id', 'victim-session')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'other-admin-session')->exists())->toBeTrue()
        ->and($user->fresh()->remember_token)->not->toBe($tokenBefore);
})->with([
    'new password' => [['password' => 'password-baru-123']],
    'new role' => [['account_type' => 'user']],
]);

test('a profile-only edit leaves the user signed in', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    DB::table('sessions')->insert(['id' => 'keep-me', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

    $this->actingAs($admin)->put(route('admin.users.update', $user), ['name' => 'Nama Baru', 'email' => $user->email, 'account_type' => 'user']);

    expect(DB::table('sessions')->where('id', 'keep-me')->exists())->toBeTrue();
});

test('deleting a user ends their sessions', function () {
    config(['session.driver' => 'database']);
    $this->withSession(['auth.password_confirmed_at' => time()]);
    $user = User::factory()->create();
    DB::table('sessions')->insert(['id' => 'gone', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

    $this->actingAs(User::factory()->admin()->create())->delete(route('admin.users.destroy', $user))->assertRedirect();

    expect(DB::table('sessions')->where('id', 'gone')->exists())->toBeFalse();
});
