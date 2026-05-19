<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    private const NAME_MAX_LENGTH = 100;
    private const USERNAME_MAX_LENGTH = 50;
    private const PASSWORD_MAX_LENGTH = 255;
    private const PASSWORD_MIN_LENGTH = 8;

    public function index(Request $request)
    {
        $users = User::query()
            ->orderByRaw("CASE WHEN role = 'super_admin' THEN 1 WHEN role = 'admin' THEN 2 WHEN role = 'staff' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        return view('internal.users.index', [
            'users' => $users,
            'roleLabels' => User::ROLE_LABELS,
        ]);
    }

    public function create(Request $request)
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('internal.users.form', [
            'user' => null,
            'allowedRoles' => $this->allowedRolesForActor($actor),
            'roleLabels' => User::ROLE_LABELS,
            'nameMax' => self::NAME_MAX_LENGTH,
            'usernameMax' => self::USERNAME_MAX_LENGTH,
            'passwordMin' => self::PASSWORD_MIN_LENGTH,
        ]);
    }

    public function store(Request $request)
    {
        /** @var User $actor */
        $actor = $request->user();

        $allowedRoles = $this->allowedRolesForActor($actor);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:' . self::NAME_MAX_LENGTH],
            'username' => ['required', 'string', 'regex:/^[a-zA-Z0-9_]+$/', 'max:' . self::USERNAME_MAX_LENGTH, 'unique:users,username'],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'password' => ['required', 'string', 'min:' . self::PASSWORD_MIN_LENGTH, 'max:' . self::PASSWORD_MAX_LENGTH],
        ]);

        /** @var User $user */
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

        if (!$actor->canManageUser($user)) {
             return redirect()->route('internal.users.index')
                ->with('status', 'User berhasil dibuat.');
        }

        return redirect()->route('internal.users.edit', ['user' => $user->id])
            ->with('status', 'User berhasil dibuat.');
    }

    public function edit(Request $request, User $user)
    {
        /** @var User $actor */
        $actor = $request->user();

        if (!$actor->canManageUser($user)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return view('internal.users.form', [
            'user' => $user,
            'allowedRoles' => $this->allowedRolesForActor($actor, $user),
            'roleLabels' => User::ROLE_LABELS,
            'nameMax' => self::NAME_MAX_LENGTH,
            'usernameMax' => self::USERNAME_MAX_LENGTH,
            'passwordMin' => self::PASSWORD_MIN_LENGTH,
        ]);
    }

    public function update(Request $request, User $user)
    {
        /** @var User $actor */
        $actor = $request->user();

        if (!$actor->canManageUser($user)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $allowedRoles = $this->allowedRolesForActor($actor, $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:' . self::NAME_MAX_LENGTH],
            'username' => ['required', 'string', 'regex:/^[a-zA-Z0-9_]+$/', 'max:' . self::USERNAME_MAX_LENGTH, Rule::unique('users', 'username')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'password' => ['nullable', 'string', 'min:' . self::PASSWORD_MIN_LENGTH, 'max:' . self::PASSWORD_MAX_LENGTH],
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

}
