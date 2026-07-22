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
        $maxAttempts = 50; // matches the limit chatbot routes previously used via throttle:50,1
        $decayMinutes = 1;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terlalu banyak permintaan. Tunggu beberapa menit.',
                'retryable' => false,
            ], 429);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);
        $response->headers->set('X-RateLimit-Remaining', (string) $this->limiter->remaining($key, $maxAttempts));

        return $response;
    }

    private function getKey(Request $request): string
    {
        $identifier = auth()->id() ?? $request->ip();
        return "chatbot:{$identifier}";
    }
}
