<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingUser->assignRole('admin');
    }

    public function test_a_regular_user_cannot_access_role_management(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('user');

        $this->actingAs($regularUser)
            ->getJson('/api/admin/roles')
            ->assertForbidden();
    }

    public function test_an_admin_can_list_roles_and_available_permissions(): void
    {
        $response = $this->getJson('/api/admin/roles');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonCount(5, 'permissions')
            ->assertJsonFragment(['name' => 'roles.manage']);
    }

    public function test_an_admin_can_create_and_update_a_role_with_permissions(): void
    {
        $createResponse = $this->postJson('/api/admin/roles', [
            'name' => 'recruiter',
            'permissions' => ['users.view', 'users.update'],
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('name', 'recruiter')
            ->assertJsonCount(2, 'permissions');

        $role = Role::query()->where('name', 'recruiter')->firstOrFail();

        $updateResponse = $this->putJson("/api/admin/roles/{$role->id}", [
            'name' => 'talent_partner',
            'permissions' => ['users.view'],
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('name', 'talent_partner')
            ->assertJsonCount(1, 'permissions')
            ->assertJsonPath('permissions.0.name', 'users.view');
    }

    public function test_base_roles_cannot_be_renamed_or_deleted(): void
    {
        $admin = Role::query()->where('name', 'admin')->firstOrFail();

        $this->putJson("/api/admin/roles/{$admin->id}", [
            'name' => 'super_admin',
            'permissions' => Permission::query()->pluck('name')->all(),
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes renombrar los roles base admin o user.');

        $this->deleteJson("/api/admin/roles/{$admin->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Los roles base admin y user no se pueden borrar.');

        $this->assertModelExists($admin);
    }

    public function test_admin_permissions_cannot_be_removed(): void
    {
        $admin = Role::query()->where('name', 'admin')->firstOrFail();

        $this->putJson("/api/admin/roles/{$admin->id}", [
            'name' => 'admin',
            'permissions' => [],
        ])->assertOk()
            ->assertJsonCount(5, 'permissions');

        $this->assertTrue($admin->fresh()->hasAllPermissions(Permission::all()));
    }

    public function test_an_assigned_custom_role_cannot_be_deleted(): void
    {
        $role = Role::query()->create(['name' => 'assigned', 'guard_name' => 'web']);
        User::factory()->create()->assignRole($role);

        $this->deleteJson("/api/admin/roles/{$role->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes borrar un rol que todavía tiene usuarios asignados.');

        $this->assertModelExists($role);
    }

    public function test_an_admin_can_delete_a_custom_role(): void
    {
        $role = Role::query()->create(['name' => 'temporary', 'guard_name' => 'web']);

        $this->deleteJson("/api/admin/roles/{$role->id}")
            ->assertNoContent();

        $this->assertModelMissing($role);
    }

    public function test_the_permissions_seeder_is_idempotent(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(5, Permission::query()->count());
        $this->assertSame(2, Role::query()->count());
        $this->assertTrue(Role::findByName('admin')->hasAllPermissions(Permission::all()));
    }
}
