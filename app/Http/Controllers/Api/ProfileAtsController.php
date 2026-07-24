<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\AI\CvAtsOptimizer;
use App\Services\Profile\ProfileBuilder;
use App\Services\Profile\ProfileVariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ProfileAtsController extends Controller
{
    public function analyze(CvAtsOptimizer $optimizer): JsonResponse
    {
        $profile = Profile::active();

        if ($profile === null) {
            return response()->json(['message' => 'No hay un perfil activo para analizar.'], 422);
        }

        try {
            $result = $optimizer->analyze($profile);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'ats_score' => $result->atsScore,
            'problemas' => $result->problemas,
            'keywords_faltantes' => $result->keywordsFaltantes,
            'recomendaciones_formato' => $result->recomendacionesFormato,
            'before_markdown' => $profile->raw_md,
            'after_markdown' => $result->versionOptimizadaMd,
            'usage' => $result->usage,
            'model' => $result->model,
        ]);
    }

    public function confirm(Request $request, ProfileBuilder $builder, ProfileVariantService $service): JsonResponse
    {
        $validated = $request->validate([
            'version_optimizada_md' => ['required', 'string'],
        ]);

        $structured = $builder->fromMarkdown($validated['version_optimizada_md']);
        $slug = $service->uniqueSlug('ats-optimizado');

        try {
            $profile = $service->createVariant($slug, 'CV optimizado para ATS', [
                'headline' => $structured['headline'],
                'summary' => $structured['summary'],
                'experience' => $structured['experience'],
                'skills' => $structured['skills'],
                'education' => $structured['education'],
                'certifications' => $structured['certifications'],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($profile, 201);
    }
}
