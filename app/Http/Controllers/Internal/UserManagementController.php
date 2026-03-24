<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->orderByRaw("CASE WHEN role = 'super_admin' THEN 1 WHEN role = 'admin' THEN 2 WHEN role = 'staff' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('internal.users.index', [
            'users' => $users,
        ]);
    }

    public function create(Request $request)
    {
        $actor = $request->user();

        return view('internal.users.form', [
            'user' => null,
            'allowedRoles' => $this->allowedRolesForActor($actor),
        ]);
    }

    public function store(Request $request)
    {
        $actor = $request->user();

        $allowedRoles = $this->allowedRolesForActor($actor);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:10'],
            'username' => ['required', 'string', 'regex:/^[a-zA-Z0-9_]+$/', 'max:50', 'unique:users,username'],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        Audit::log($request, 'user.created', $user, [
            'name' => $user->name,
            'role' => $user->role,
        ]);

        if (!$this->canManageUser($actor, $user)) {
             return redirect()->route('internal.users.index')
                ->with('status', 'User berhasil dibuat.');
        }

        return redirect()->route('internal.users.edit', ['user' => $user->id])
            ->with('status', 'User berhasil dibuat.');
    }

    public function edit(Request $request, User $user)
    {
        $actor = $request->user();

        if (!$this->canManageUser($actor, $user)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return view('internal.users.form', [
            'user' => $user,
            'allowedRoles' => $this->allowedRolesForActor($actor, $user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $actor = $request->user();

        if (!$this->canManageUser($actor, $user)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $allowedRoles = $this->allowedRolesForActor($actor, $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:10'],
            'username' => ['required', 'string', 'regex:/^[a-zA-Z0-9_]+$/', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        $user->fill($request->only(['name', 'username', 'role']));
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $changed = array_keys($user->getDirty());
        $user->save();

        Audit::log($request, 'user.updated', $user, [
            'name' => $user->name,
            'changed' => $changed,
            'role' => $user->role,
        ]);

        return redirect()->route('internal.users.edit', ['user' => $user->id])
            ->with('status', 'Perubahan tersimpan.');
    }

    /** @return array<int,string> */
    private function allowedRolesForActor(User $actor, ?User $editingUser = null): array
    {
        if ($actor->isSuperAdmin()) {
            $roles = [User::ROLE_STAFF, User::ROLE_ADMIN];

            // Allow super_admin role only if no other super_admin exists,
            // or the user being edited IS already the super_admin.
            $existingSuperAdminId = User::where('role', User::ROLE_SUPER_ADMIN)
                ->value('id');

            if (!$existingSuperAdminId || ($editingUser && $existingSuperAdminId === $editingUser->id)) {
                $roles[] = User::ROLE_SUPER_ADMIN;
            }

            return $roles;
        }

        if ($actor->isAdmin()) {
            return $editingUser ? [User::ROLE_STAFF, User::ROLE_ADMIN] : [User::ROLE_STAFF];
        }

        return [];
    }

    public function canManageUser(User $actor, User $target): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($actor->isAdmin()) {
            // Admin can edit themselves
            if ($actor->id === $target->id) {
                return true;
            }

            // Admin can only edit staff
            return $target->role === User::ROLE_STAFF;
        }

        return false;
    }
}
