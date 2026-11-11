<?php

namespace Tests\Feature;

use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The landing page renders with the seeded site settings.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        (new SettingsSeeder)->run();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('WatchEarn');
    }
}
