<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example for transaction index.
     *
     * @return void
     */
    public function test_transaction_index_returns_successful_response()
    {
        // Smoke test to ensure the base URL returns a 200 or 302 (redirect to login)
        $response = $this->get('/');
        
        $response->assertStatus(200);
    }
}
