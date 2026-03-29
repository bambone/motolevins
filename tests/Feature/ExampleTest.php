<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->withoutVite();

        // Domain routes are registered at boot from phpunit.xml (TENANCY_CENTRAL_DOMAINS=apex.test).
        $response = $this->call('GET', 'http://apex.test/');

        $response->assertStatus(200);
    }
}
