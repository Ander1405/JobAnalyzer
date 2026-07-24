<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\Profile\ProfileVariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class ProfileVariantController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Profile::orderByDesc('is_active')->orderBy('slug')->get());
    }

    public function show(Profile $profile): JsonResponse
    {
        return response()->json($profile);
    }

    public function store(Request $request, ProfileVariantService $service): JsonResponse
    {
        $validated = $request->validate([
            'slug' => [
                'required', 'string', 'alpha_dash', 'max:50',
                Rule::unique('profiles', 'slug')->where('user_id', $request->user()->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'headline' => ['nullable', 'string'],
            'summary' => ['nullable', 'string'],
            'experience' => ['nullable', 'array'],
            'skills' => ['nullable', 'array'],
            'education' => ['nullable', 'array'],
            'certifications' => ['nullable', 'array'],
        ]);

        try {
            $profile = $service->createVariant(
                $validated['slug'],
                $validated['label'],
                array_filter($validated, fn ($key) => ! in_array($key, ['slug', 'label'], true), ARRAY_FILTER_USE_KEY),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($profile, 201);
    }

    public function update(Request $request, Profile $profile, ProfileVariantService $service): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:255'],
            'contact' => ['sometimes', 'array'],
            'headline' => ['sometimes', 'nullable', 'string'],
            'summary' => ['sometimes', 'nullable', 'string'],
            'experience' => ['sometimes', 'array'],
            'skills' => ['sometimes', 'array'],
            'education' => ['sometimes', 'array'],
            'languages' => ['sometimes', 'array'],
            'certifications' => ['sometimes', 'array'],
        ]);

        $profile->update($validated);
        $profile = $service->regenerateMarkdown($profile);

        return response()->json($profile);
    }

    public function sync(Request $request, Profile $profile, ProfileVariantService $service): JsonResponse
    {
        if (! $profile->is_active) {
            return response()->json([
                'message' => 'Only the active profile can be synced from storage/app/perfil.md.',
            ], 409);
        }

        $validated = $request->validate(['content' => ['sometimes', 'string']]);

        try {
            $profile = $service->syncActiveFromFile($validated['content'] ?? null);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($profile);
    }

    public function activate(Profile $profile, ProfileVariantService $service): JsonResponse
    {
        return response()->json($service->activate($profile));
    }
}
