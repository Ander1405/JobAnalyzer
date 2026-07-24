<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Auth::guard('web')->logout();
        Role::query()->create(['name' => 'user']);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Auth/Register'));
    }

    public function test_guest_can_register_and_receives_the_user_role(): void
    {
        Event::fake();

        $response = $this->post('/register', [
            'name' => 'Ana Torres',
            'email' => 'ana@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = User::query()->where('email', 'ana@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('user'));
        $response->assertRedirect('/marketplace');
        Event::assertDispatched(
            Registered::class,
            fn (Registered $event): bool => $event->user->is($user),
        );
    }

    public function test_duplicate_email_fails_validation(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'ana@example.com',
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Otra Ana',
            'email' => $existingUser->email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'email' => 'Ya existe una cuenta con este email.',
        ]);
        $this->assertSame(2, User::query()->count());
    }

    public function test_registration_rolls_back_when_the_default_role_is_missing(): void
    {
        Role::query()->where('name', 'user')->delete();
        $this->withoutExceptionHandling();

        try {
            $this->post('/register', [
                'name' => 'Ana Torres',
                'email' => 'ana@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

            $this->fail('Expected the missing role to stop registration.');
        } catch (RoleDoesNotExist) {
            $this->assertNull(
                User::query()->where('email', 'ana@example.com')->first(),
            );
            $this->assertGuest();
        }
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/register', []);
        }

        $this->post('/register', [])->assertTooManyRequests();
    }
}
