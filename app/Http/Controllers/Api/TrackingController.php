<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\CommentType;
use App\Enums\TrackedJobPriority;
use App\Enums\TrackedJobStatus;
use App\Http\Controllers\Controller;
use App\Models\TrackedJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class TrackingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            TrackedJob::query()->with(['job', 'latestComment'])->latest()->get(),
        );
    }

    public function show(TrackedJob $trackedJob): JsonResponse
    {
        return response()->json($trackedJob->load([
            'job',
            'job.latestCvVariant' => fn ($query) => $query->select(
                'profiles.id', 'profiles.job_id', 'profiles.slug', 'profiles.label', 'profiles.updated_at',
            ),
            'comments' => fn ($query) => $query->orderBy('created_at'),
        ]));
    }

    public function update(Request $request, TrackedJob $trackedJob): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', new Enum(TrackedJobStatus::class)],
            'priority' => ['sometimes', 'nullable', new Enum(TrackedJobPriority::class)],
            'next_action' => ['sometimes', 'nullable', 'string', 'max:255'],
            'next_action_date' => ['sometimes', 'nullable', 'date'],
            'cv_version_used' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $trackedJob->update($validated);

        return response()->json($trackedJob->fresh([
            'job',
            'job.latestCvVariant' => fn ($query) => $query->select(
                'profiles.id', 'profiles.job_id', 'profiles.slug', 'profiles.label', 'profiles.updated_at',
            ),
            'comments' => fn ($query) => $query->orderBy('created_at'),
        ]));
    }

    public function storeComment(Request $request, TrackedJob $trackedJob): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'type' => ['sometimes', Rule::in([
                CommentType::Nota->value,
                CommentType::Entrevista->value,
                CommentType::Seguimiento->value,
            ])],
        ]);

        $comment = $trackedJob->comments()->create([
            'body' => $validated['body'],
            'type' => $validated['type'] ?? CommentType::Nota,
        ]);

        return response()->json($comment, Response::HTTP_CREATED);
    }

    public function destroy(TrackedJob $trackedJob): JsonResponse
    {
        $trackedJob->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
