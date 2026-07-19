<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\AI\ProfileReviewer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ProfileReviewController extends Controller
{
    public function review(Profile $profile, ProfileReviewer $reviewer): JsonResponse
    {
        try {
            $result = $reviewer->review($profile);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'suggestions' => $result->suggestions,
            'usage' => $result->usage,
            'model' => $result->model,
        ]);
    }

    public function apply(Request $request, Profile $profile, ProfileReviewer $reviewer): JsonResponse
    {
        $validated = $request->validate([
            'suggestions' => ['required', 'array'],
            'suggestions.*.field' => [
                'required', 'string',
                'in:headline,summary,english_level,experience,skills,education,certifications,languages',
            ],
            'suggestions.*.action' => ['required', 'string', 'in:replace,add,remove'],
            'suggestions.*.index' => ['nullable', 'integer', 'min:0'],
            'suggestions.*.suggested' => ['nullable', 'string'],
        ]);

        foreach ($validated['suggestions'] as $suggestion) {
            if ($suggestion['action'] !== 'remove' && ! is_string($suggestion['suggested'] ?? null)) {
                return response()->json([
                    'message' => 'Every non-remove suggestion must include a string [suggested] value.',
                ], 422);
            }
        }

        $profile = $reviewer->applySuggestions($profile, $validated['suggestions']);

        return response()->json($profile);
    }
}
