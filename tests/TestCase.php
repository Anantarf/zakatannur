<?php

namespace Tests;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Disable CSRF token verification for all feature tests.
     * In the test environment (SESSION_DRIVER=array), the cookie-based CSRF
     * token is never populated, causing all POST/PATCH/DELETE requests to
     * return HTTP 419. Disabling only this middleware keeps every other
     * security layer (auth, role, policy) fully active.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }
}
