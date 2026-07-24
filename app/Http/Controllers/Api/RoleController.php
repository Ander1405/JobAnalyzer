<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** @var array<int, string> */
    private const PROTECTED_ROLES = ['admin', 'user'];

    public function index(): JsonResponse
    {
        Gate::authorize('roles.manage');

        return response()->json([
            'data' => Role::query()
                ->select(['id', 'name', 'guard_name', 'created_at', 'updated_at'])
                ->with('permissions:id,name')
                ->withCount('users')
                ->orderBy('name')
                ->get(),
            'permissions' => Permission::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(RoleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $role = DB::transaction(function () use ($validated): Role {
            $role = Role::query()->create([
                'name' => $validated['name'],
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($validated['permissions']);

            return $role;
        });

        return response()->json(
            $role->load('permissions:id,name')->loadCount('users'),
            Response::HTTP_CREATED,
        );
    }

    public function update(RoleRequest $request, Role $role): JsonResponse
    {
        $validated = $request->validated();

        if ($this->isProtected($role) && $validated['name'] !== $role->name) {
            return response()->json([
                'message' => 'No puedes renombrar los roles base admin o user.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $permissions = $role->name === 'admin'
            ? Permission::query()->pluck('name')->all()
            : $validated['permissions'];

        DB::transaction(function () use ($role, $validated, $permissions): void {
            $role->update(['name' => $validated['name']]);
            $role->syncPermissions($permissions);
        });

        return response()->json(
            $role->load('permissions:id,name')->loadCount('users'),
        );
    }

    public function destroy(Role $role): Response|JsonResponse
    {
        Gate::authorize('roles.manage');

        if ($this->isProtected($role)) {
            return response()->json([
                'message' => 'Los roles base admin y user no se pueden borrar.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'No puedes borrar un rol que todavía tiene usuarios asignados.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $role->delete();

        return response()->noContent();
    }

    private function isProtected(Role $role): bool
    {
        return in_array($role->name, self::PROTECTED_ROLES, true);
    }
}
