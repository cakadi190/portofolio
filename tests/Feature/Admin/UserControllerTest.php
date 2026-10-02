<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

test('a user can be created with a library avatar', function () {
    $media = Media::factory()->create();

    $response = $this->actingAs(User::factory()->create())->post(route('admin.users.store'), [
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
    $response = $this->actingAs(User::factory()->create())
        ->post(route('admin.users.store'), []);

    $response->assertSessionHasErrors(['name', 'email', 'password', 'account_type']);
});

test('updating a user without a password keeps the previous password', function () {
    $user = User::factory()->create();
    $originalPassword = $user->password;

    $response = $this->actingAs(User::factory()->create())->put(route('admin.users.update', $user), [
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

    $response = $this->actingAs(User::factory()->create())->put(route('admin.users.update', $user), [
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

    $response = $this->actingAs(User::factory()->create())
        ->delete(route('admin.users.destroy', $user));

    $response->assertRedirect(route('admin.users.index'));
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});
