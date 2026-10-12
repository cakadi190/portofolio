<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Gender;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use PaginatesTables;

    public function __construct(private readonly AuditLogger $audit) {}

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
        $data['email_verified_at'] = now();

        User::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna berhasil ditambahkan.']);

        return to_route('admin.users.index');
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($user->isAdmin() && $data['account_type'] !== UserRole::Admin->value && ! $this->hasAnotherAdmin($user)) {
            Inertia::flash('toast', ['type' => 'danger', 'message' => 'Admin terakhir tidak dapat diturunkan perannya.']);

            return back();
        }

        if (filled($data['password'] ?? null)) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $previousRole = $user->account_type;
        $user->update($data);

        if ($previousRole !== $user->account_type) {
            $this->audit->record('users', 'role_changed', 'Peran pengguna diubah', $user, ['from' => $previousRole->value, 'to' => $user->account_type->value], causer: $request->user());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna berhasil diperbarui.']);

        return to_route('admin.users.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            Inertia::flash('toast', ['type' => 'danger', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.']);

            return back();
        }

        if ($user->isAdmin() && ! $this->hasAnotherAdmin($user)) {
            Inertia::flash('toast', ['type' => 'danger', 'message' => 'Admin terakhir tidak dapat dihapus.']);

            return back();
        }

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna berhasil dihapus.']);

        return to_route('admin.users.index');
    }

    /**
     * Whether any admin other than the given user exists, so the site can
     * never be left without someone able to manage it.
     */
    private function hasAnotherAdmin(User $user): bool
    {
        return User::query()
            ->where('account_type', UserRole::Admin)
            ->whereKeyNot($user->getKey())
            ->exists();
    }
}
