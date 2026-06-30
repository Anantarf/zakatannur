<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function showChallenge()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (session('2fa_passed')) {
            return redirect()->intended('/dashboard');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $user = Auth::user();
        $secret = decrypt($user->two_factor_secret);

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'Kode tidak valid atau sudah kedaluwarsa.']);
        }

        session(['2fa_passed' => true]);

        return redirect()->intended('/dashboard');
    }
}
