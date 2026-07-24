<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_is_public(): void
    {
        Auth::guard('web')->logout();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Marketing/Landing'));
    }

    public function test_authenticated_user_is_redirected_to_marketplace(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/marketplace');
    }
}
