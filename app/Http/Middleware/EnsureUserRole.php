<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        if (empty($roles)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(Response::HTTP_FORBIDDEN);
    }
}
