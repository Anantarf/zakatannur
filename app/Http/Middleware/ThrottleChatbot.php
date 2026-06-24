<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleChatbot
{
    public function __construct(private RateLimiter $limiter)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->getKey($request);
        $maxAttempts = 30; // 30 requests per minute
        $decayMinutes = 1;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terlalu banyak permintaan. Tunggu beberapa menit.',
                'retryable' => false,
            ], 429);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        return $next($request)->header('X-RateLimit-Remaining', $this->limiter->remaining($key, $maxAttempts));
    }

    private function getKey(Request $request): string
    {
        $identifier = auth()->id() ?? $request->ip();
        return "chatbot:{$identifier}";
    }
}
