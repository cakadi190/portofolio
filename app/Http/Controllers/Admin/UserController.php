<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Gender;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/users/index', [
            'users' => $this->paginateTable(
                User::query()->orderBy('name'),
                $request,
                ['name', 'email', 'phone'],
                ['name', 'email', 'account_type', 'phone'],
            ),
            'filters' => $this->tableFilters($request, ['name', 'email', 'account_type', 'phone']),
            'accountTypes' => UserRole::options(),
            'genders' => Gender::options(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        User::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna berhasil ditambahkan.']);

        return to_route('admin.users.index');
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (filled($data['password'] ?? null)) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna berhasil diperbarui.']);

        return to_route('admin.users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna berhasil dihapus.']);

        return to_route('admin.users.index');
    }
}
