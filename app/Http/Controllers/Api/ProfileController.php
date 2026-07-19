<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Profile\ProfileConverter;
use App\Services\Profile\ResumeTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use RuntimeException;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $path = storage_path('app/perfil.md');

        return response()->json([
            'content' => file_exists($path) ? file_get_contents($path) : '',
        ]);
    }

    public function upload(Request $request, ResumeTextExtractor $extractor, ProfileConverter $converter): JsonResponse
    {
        $request->validate([
            'resume' => ['required', File::types(['pdf'])->max(10 * 1024)],
        ]);

        $file = $request->file('resume');

        try {
            $text = $extractor->extract($file->getRealPath());
            $completion = $converter->convert($text);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $file->storeAs('', 'resume.pdf');
        file_put_contents(storage_path('app/perfil.md'), $completion->text);

        return response()->json([
            'content' => $completion->text,
            'model' => $completion->model,
            'usage' => $completion->usage,
        ]);
    }
}
