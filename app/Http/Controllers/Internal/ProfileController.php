<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    private const PASSWORD_MIN_LENGTH = 8;

    public function edit(Request $request)
    {
        return view('internal.profile.edit', [
            'user' => $request->user(),
            'nameMax' => (int) config('zakat.validation.user_name_max', 100),
            'usernameMax' => (int) config('zakat.validation.username_max', 50),
            'passwordMin' => self::PASSWORD_MIN_LENGTH,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:' . (int) config('zakat.validation.user_name_max', 100)],
            'username' => [
                'required', 
                'string', 
                'regex:/^[a-zA-Z0-9_]+$/', 
                'max:' . (int) config('zakat.validation.username_max', 50), 
                Rule::unique('users', 'username')->ignore($user->id)
            ],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:' . self::PASSWORD_MIN_LENGTH, 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->username = $data['username'];

        if ($request->filled('password')) {
            $user->password = Hash::make($data['password']);
        }

        $changed = array_keys($user->getDirty());
        $user->save();

        Audit::log($request, 'profile.updated', $user, [
            'changed' => $changed,
        ]);

        return redirect()->route('internal.profile.edit')
            ->with('status', 'Profil berhasil diperbarui.');
    }
}
