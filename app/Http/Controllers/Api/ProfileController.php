<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\Profile\CvImportService;
use App\Support\ProfileFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $path = ProfileFile::path();

        return response()->json([
            'content' => file_exists($path) ? file_get_contents($path) : '',
            'profile' => Profile::active(),
        ]);
    }

    public function import(Request $request, CvImportService $service): JsonResponse
    {
        $request->validate([
            'cv' => ['required', File::types(['pdf', 'txt', 'md'])->max(10 * 1024)],
        ]);

        $file = $request->file('cv');

        try {
            $profile = $service->import($file->getRealPath(), 'default', $file->getClientOriginalExtension());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'content' => $profile->raw_md,
            'profile' => $profile,
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }
}
