<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingUser->assignRole('admin');
    }

    public function test_a_regular_user_cannot_access_user_management(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('user');

        $this->actingAs($regularUser)
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_an_admin_can_search_and_filter_paginated_users_with_roles(): void
    {
        $matchingUser = User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);
        $matchingUser->assignRole('user');

        User::factory()->create(['name' => 'Grace Hopper'])->assignRole('admin');

        $response = $this->getJson('/api/admin/users?search=ada&role=user&per_page=5');

        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.email', 'ada@example.com')
            ->assertJsonPath('data.0.roles.0.name', 'user');
    }

    public function test_an_admin_can_create_a_user_and_assign_roles(): void
    {
        $response = $this->postJson('/api/admin/users', [
            'name' => 'Nueva Persona',
            'email' => 'new@example.com',
            'password' => 'password',
            'roles' => ['user'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('email', 'new@example.com')
            ->assertJsonPath('roles.0.name', 'user');

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertTrue($user->hasRole('user'));
    }

    public function test_an_admin_can_update_user_data_roles_and_password(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->putJson("/api/admin/users/{$user->id}", [
            'name' => 'Persona Editada',
            'email' => 'updated@example.com',
            'password' => 'new-password',
            'roles' => ['admin'],
        ]);

        $response->assertOk()
            ->assertJsonPath('name', 'Persona Editada')
            ->assertJsonPath('roles.0.name', 'admin');

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('user'));
    }

    public function test_the_last_admin_cannot_remove_their_admin_role(): void
    {
        $response = $this->putJson("/api/admin/users/{$this->actingUser->id}", [
            'name' => $this->actingUser->name,
            'email' => $this->actingUser->email,
            'password' => null,
            'roles' => ['user'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Debe existir al menos un administrador.');

        $this->assertTrue($this->actingUser->fresh()->hasRole('admin'));
    }

    public function test_the_last_admin_cannot_be_deleted(): void
    {
        $response = $this->deleteJson("/api/admin/users/{$this->actingUser->id}");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes borrar al último administrador.');

        $this->assertModelExists($this->actingUser);
    }

    public function test_an_admin_cannot_delete_their_own_account(): void
    {
        User::factory()->create()->assignRole('admin');

        $response = $this->deleteJson("/api/admin/users/{$this->actingUser->id}");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes borrar tu propia cuenta.');

        $this->assertModelExists($this->actingUser);
    }

    public function test_the_configured_owner_cannot_be_deleted(): void
    {
        config(['jobhunter.owner.email' => 'owner@example.com']);
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $owner->assignRole('user');

        $response = $this->deleteJson("/api/admin/users/{$owner->id}");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes borrar la cuenta propietaria de la aplicación.');

        $this->assertModelExists($owner);
    }

    public function test_the_configured_owners_email_cannot_be_changed(): void
    {
        config(['jobhunter.owner.email' => 'owner@example.com']);
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $owner->assignRole('user');

        $response = $this->putJson("/api/admin/users/{$owner->id}", [
            'name' => $owner->name,
            'email' => 'other@example.com',
            'password' => null,
            'roles' => ['user'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes cambiar el correo de la cuenta propietaria de la aplicación.');

        $this->assertSame('owner@example.com', $owner->fresh()->email);
    }

    public function test_an_admin_can_delete_another_regular_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->deleteJson("/api/admin/users/{$user->id}")
            ->assertNoContent();

        $this->assertModelMissing($user);
    }
}
