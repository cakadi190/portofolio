<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('guests are redirected to the login page', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

test('a user can be created with an avatar upload', function () {
    $response = $this->actingAs(User::factory()->create())->post(route('admin.users.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'account_type' => 'user',
        'avatar' => UploadedFile::fake()->image('avatar.png'),
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();
    expect($user->avatar)->not->toBeNull();
    expect(Hash::check('password123', $user->password))->toBeTrue();
    Storage::disk('public')->assertExists($user->avatar);
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

test('updating a user replaces the previous avatar', function () {
    $user = User::factory()->create(['avatar' => 'avatars/old.png']);
    Storage::disk('public')->put('avatars/old.png', 'fake-content');

    $response = $this->actingAs(User::factory()->create())->put(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'account_type' => 'user',
        'avatar' => UploadedFile::fake()->image('new.png'),
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $user->refresh();
    Storage::disk('public')->assertMissing('avatars/old.png');
    Storage::disk('public')->assertExists($user->avatar);
});

test('a user can be deleted along with its avatar', function () {
    $user = User::factory()->create(['avatar' => 'avatars/avatar.png']);
    Storage::disk('public')->put('avatars/avatar.png', 'fake-content');

    $response = $this->actingAs(User::factory()->create())
        ->delete(route('admin.users.destroy', $user));

    $response->assertRedirect(route('admin.users.index'));
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    Storage::disk('public')->assertMissing('avatars/avatar.png');
});
