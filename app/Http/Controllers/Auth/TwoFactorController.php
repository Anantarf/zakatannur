<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function showChallenge(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (session('2fa_passed')) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        $user = Auth::user();

        if (!$user || !$user->hasTwoFactorEnabled()) {
            return redirect()->route('dashboard');
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $request->input('code')
        );

        if (!$valid) {
            return back()->withErrors(['code' => 'Kode salah atau sudah kedaluwarsa. Coba lagi.']);
        }

        session(['2fa_passed' => true]);

        return redirect()->intended(route('dashboard'));
    }
}
