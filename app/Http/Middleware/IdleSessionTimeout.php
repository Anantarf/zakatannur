<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdleSessionTimeout
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $idle = (int) env('SESSION_IDLE_TIMEOUT', 60) * 60;
            if (time() - session('last_activity', time()) > $idle) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->with('status', 'Sesi habis karena tidak aktif.');
            }
            session(['last_activity' => time()]);
        }

        return $next($request);
    }
}
