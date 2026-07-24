<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('users.view');

        $search = trim($request->string('search')->toString());
        $role = trim($request->string('role')->toString());
        $perPage = min(100, max(5, $request->integer('per_page', 15)));

        $users = User::query()
            ->select(['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at'])
            ->with('roles:id,name')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', fn (Builder $query) => $query->role($role))
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($users);
    }

    public function store(UserStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
            $user->syncRoles($validated['roles']);

            return $user;
        });

        return response()->json($user->load('roles:id,name'), Response::HTTP_CREATED);
    }

    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        if (
            strcasecmp($user->email, (string) config('jobhunter.owner.email')) === 0
            && strcasecmp($validated['email'], $user->email) !== 0
        ) {
            return response()->json([
                'message' => 'No puedes cambiar el correo de la cuenta propietaria de la aplicación.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($user, $validated): void {
            if (
                $user->hasRole('admin')
                && ! in_array('admin', $validated['roles'], true)
                && $this->adminCountForUpdate() <= 1
            ) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Debe existir al menos un administrador.');
            }

            $attributes = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];

            if (! empty($validated['password'])) {
                $attributes['password'] = $validated['password'];
            }

            $user->update($attributes);
            $user->syncRoles($validated['roles']);
        });

        return response()->json($user->fresh()->load('roles:id,name'));
    }

    public function destroy(Request $request, User $user): Response|JsonResponse
    {
        Gate::authorize('users.delete');

        return DB::transaction(function () use ($request, $user): Response|JsonResponse {
            if ($user->hasRole('admin') && $this->adminCountForUpdate() <= 1) {
                return response()->json([
                    'message' => 'No puedes borrar al último administrador.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($request->user()->is($user)) {
                return response()->json([
                    'message' => 'No puedes borrar tu propia cuenta.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (strcasecmp($user->email, (string) config('jobhunter.owner.email')) === 0) {
                return response()->json([
                    'message' => 'No puedes borrar la cuenta propietaria de la aplicación.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user->delete();

            return response()->noContent();
        });
    }

    private function adminCountForUpdate(): int
    {
        $adminIds = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'admin'))
            ->lockForUpdate()
            ->pluck('users.id')
            ->all();

        return count($adminIds);
    }
}
