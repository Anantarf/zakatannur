<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class ProfileTwoFactorController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $qrCodeUrl = null;

        if ($user->two_factor_secret && !$user->two_factor_confirmed_at) {
            $google2fa = new Google2FA();
            $qrCodeUrl = $google2fa->getQRCodeUrl(
                config('app.name'),
                $user->username ?? $user->name,
                decrypt($user->two_factor_secret)
            );
        }

        return view('internal.profile.two-factor', compact('user', 'qrCodeUrl'));
    }

    public function enable(Request $request)
    {
        $user = Auth::user();
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $user->forceFill(['two_factor_secret' => encrypt($secret)])->save();

        return back();
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $user = Auth::user();
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey(decrypt($user->two_factor_secret), $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'Kode tidak valid. Pastikan waktu perangkat Anda akurat.']);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        session(['2fa_passed' => true]);

        return back()->with('status', '2FA berhasil diaktifkan.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        $user->forceFill([
            'two_factor_secret'       => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        session()->forget('2fa_passed');

        return back()->with('status', '2FA berhasil dinonaktifkan.');
    }
}
