<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Gender;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use HandlesUploads;

    public function index(): Response
    {
        return Inertia::render('admin/users/index', [
            'users' => User::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/create', [
            'accountTypes' => UserRole::options(),
            'genders' => Gender::options(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('avatar');
        $data['password'] = Hash::make($data['password']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->storeUpload($request->file('avatar'), 'avatars');
        }

        User::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna berhasil ditambahkan.']);

        return to_route('admin.users.index');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/edit', [
            'user' => $user,
            'accountTypes' => UserRole::options(),
            'genders' => Gender::options(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except('avatar');

        if (filled($data['password'] ?? null)) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($request->hasFile('avatar')) {
            $this->deleteUpload($user->avatar);
            $data['avatar'] = $this->storeUpload($request->file('avatar'), 'avatars');
        }

        $user->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna berhasil diperbarui.']);

        return to_route('admin.users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->deleteUpload($user->avatar);
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna berhasil dihapus.']);

        return to_route('admin.users.index');
    }
}
