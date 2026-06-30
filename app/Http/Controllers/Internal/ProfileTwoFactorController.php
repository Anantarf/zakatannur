<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class ProfileTwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $qrCodeUrl = null;

        if ($user->two_factor_secret && !$user->two_factor_confirmed_at) {
            $qrCodeUrl = $this->google2fa->getQRCodeUrl(
                config('app.name'),
                $user->username,
                decrypt($user->two_factor_secret)
            );
        }

        return view('internal.profile.two-factor', compact('user', 'qrCodeUrl'));
    }

    public function enable(Request $request)
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('status', '2FA sudah aktif.');
        }

        $secret = $this->google2fa->generateSecretKey();
        $user->update(['two_factor_secret' => encrypt($secret), 'two_factor_confirmed_at' => null]);

        return redirect()->route('profile.two-factor.show')
            ->with('status', 'Scan QR code dengan Google Authenticator, lalu masukkan kode untuk konfirmasi.');
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        $user = $request->user();

        if (!$user->two_factor_secret) {
            return back()->withErrors(['code' => 'Setup 2FA belum dimulai.']);
        }

        $valid = $this->google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $request->input('code')
        );

        if (!$valid) {
            return back()->withErrors(['code' => 'Kode salah. Pastikan waktu HP sudah sinkron.']);
        }

        $user->update(['two_factor_confirmed_at' => now()]);
        session(['2fa_passed' => true]);

        return redirect()->route('profile.two-factor.show')
            ->with('status', '2FA berhasil diaktifkan.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = $request->user();

        if (!Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        session()->forget('2fa_passed');

        return redirect()->route('profile.two-factor.show')
            ->with('status', '2FA berhasil dinonaktifkan.');
    }
}
