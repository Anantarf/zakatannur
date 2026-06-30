<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTwoFactorAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->hasTwoFactorEnabled() && !session('2fa_passed')) {
            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
