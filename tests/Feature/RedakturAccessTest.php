<?php

use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;

test('registration always creates a plain user, whatever the client sends', function () {
    $this->post(route('register.store'), [
        'name' => 'Penyusup',
        'email' => 'intruder@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => 'admin',
    ]);

    expect(User::where('email', 'intruder@example.com')->firstOrFail()->account_type)->toBe(UserRole::User);
});

test('profile updates cannot change the role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Baru', 'email' => $user->email, 'account_type' => 'admin',
    ]);

    expect($user->refresh()->account_type)->toBe(UserRole::User);
});

test('only admins can promote a user to redaktur', function () {
    $user = User::factory()->create();
    $payload = ['name' => $user->name, 'email' => $user->email, 'account_type' => 'redaktur'];

    $this->actingAs(User::factory()->redaktur()->create())->put(route('admin.users.update', $user), $payload)->assertForbidden();
    expect($user->refresh()->account_type)->toBe(UserRole::User);

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.users.update', $user), $payload);
    expect($user->refresh()->account_type)->toBe(UserRole::Redaktur);
});

test('redaktur can use the blogging area', function () {
    $redaktur = User::factory()->redaktur()->create();
    $post = Post::factory()->create();

    $this->actingAs($redaktur)->get(route('dashboard'))->assertOk();
    $this->actingAs($redaktur)->get(route('admin.posts.index'))->assertOk();
    $this->actingAs($redaktur)->get(route('admin.posts.create'))->assertOk();
    $this->actingAs($redaktur)->get(route('admin.posts.edit', $post))->assertOk();
    $this->actingAs($redaktur)->get(route('admin.post-categories.index'))->assertOk();
    $this->actingAs($redaktur)->get(route('admin.tags.index'))->assertOk();
    $this->actingAs($redaktur)->get(route('admin.blog-comments.index'))->assertOk();
    $this->actingAs($redaktur)->get(route('admin.media.browse'))->assertOk();
    $this->actingAs($redaktur)->delete(route('admin.posts.destroy', $post))->assertRedirect();
});

test('every admin route outside the blogging area is admin only', function () {
    $bloggingRoutes = [
        'admin.blog-comments.', 'admin.post-categories.', 'admin.posts.', 'admin.tags.', 'admin.media.browse', 'admin.media.store',
    ];

    $restricted = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'admin.'))
        ->reject(fn ($route) => str($route->getName())->startsWith($bloggingRoutes));

    expect($restricted)->not->toBeEmpty();

    foreach ($restricted as $route) {
        expect(in_array('can:manage-site', $route->gatherMiddleware(), true))->toBeTrue($route->getName());
    }
});

test('redaktur and plain users are denied the rest of the admin area', function (string $role) {
    $user = $role === 'user' ? User::factory()->create() : User::factory()->redaktur()->create();

    foreach ([
        'admin.users.index', 'admin.system-settings.index', 'admin.portfolios.index', 'admin.portfolio-ratings.index',
        'admin.contact-messages.index', 'admin.media.index', 'admin.awards.index', 'admin.services.index',
    ] as $name) {
        $this->actingAs($user)->get(route($name))->assertForbidden();
    }

    $this->actingAs($user)->post(route('admin.users.store'), [
        'name' => 'X', 'email' => 'x@example.com', 'password' => 'password', 'account_type' => 'admin',
    ])->assertForbidden();
    $this->actingAs($user)->put(route('admin.system-settings.update'), [])->assertForbidden();
    $this->actingAs($user)->delete(route('admin.users.destroy', User::factory()->create()))->assertForbidden();

    expect(User::where('email', 'x@example.com')->exists())->toBeFalse();
})->with(['redaktur', 'user']);

test('plain users are denied the blogging area and bounced from the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('home'));
    $this->actingAs($user)->get(route('admin.posts.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.posts.store'), ['title' => 'X', 'content' => '<p>x</p>'])->assertForbidden();
    $this->actingAs($user)->post(route('admin.media.store'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.blog-comments.index'))->assertForbidden();
});

test('guests are sent to login', function () {
    $this->get(route('admin.posts.index'))->assertRedirect(route('login'));
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});
