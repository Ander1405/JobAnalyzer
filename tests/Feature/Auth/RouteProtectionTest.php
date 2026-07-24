<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_web_routes(): void
    {
        Auth::guard('web')->logout();

        $response = $this->get('/marketplace');

        $response->assertRedirect('/login');
    }

    public function test_guests_get_a_401_from_api_routes(): void
    {
        Auth::guard('web')->logout();

        $response = $this->getJson('/api/jobs');

        $response->assertUnauthorized();
    }
}
