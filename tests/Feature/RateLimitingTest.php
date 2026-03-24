<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_summary_api_is_rate_limited(): void
    {
        // Use a unique IP to avoid interference with other tests.
        $client = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);

        // 30 per minute allowed (configured in RouteServiceProvider).
        for ($i = 0; $i < 30; $i++) {
            $client->getJson('/api/public/summary?year=2026')->assertOk();
        }

        $client->getJson('/api/public/summary?year=2026')->assertStatus(429);
    }

    public function test_public_landing_page_is_rate_limited(): void
    {
        $client = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11']);

        for ($i = 0; $i < 30; $i++) {
            $client->get('/?year=2026')->assertOk();
        }

        $client->get('/?year=2026')->assertStatus(429);
    }
}


