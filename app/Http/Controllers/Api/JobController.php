<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeJobListing;
use App\Jobs\FetchJobListings;
use App\Models\Job;
use App\Services\Notion\NotionPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class JobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Job::where('status', JobStatus::Analyzed);

        if ($source = $request->query('source')) {
            $query->where('source', $source);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($request->filled('min_match')) {
            $minMatch = (int) $request->query('min_match', 75);
            $query->whereRaw("CAST(json_extract(ai_analysis, '$.match_score') AS INTEGER) >= ?", [$minMatch]);
        }

        $perPage = max(1, min(500, (int) $request->query('per_page', 20)));
        $total = $query->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) $request->query('page', 1)), $lastPage);

        $jobs = $query->orderByRaw("CAST(json_extract(ai_analysis, '$.match_score') AS INTEGER) DESC")
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $jobs->items(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'last_page' => $jobs->lastPage(),
            ],
        ]);
    }

    public function show(Job $job): JsonResponse
    {
        return response()->json($job->load('trackedJob'));
    }

    public function sources(): JsonResponse
    {
        return response()->json(
            Job::query()->distinct()->orderBy('source')->pluck('source'),
        );
    }

    public function fetch(Request $request): JsonResponse
    {
        FetchJobListings::dispatch($request->user()->id);

        return response()->json(['queued' => true], 202);
    }

    public function analyze(Job $job): JsonResponse
    {
        $job->update(['status' => JobStatus::Analyzing, 'error_message' => null]);

        // Explicit request for this listing: the profile pre-filter does not get a vote.
        AnalyzeJobListing::dispatch($job, skipPreFilter: true);

        return response()->json($job->fresh());
    }

    public function publish(Job $job, NotionPublisher $publisher): JsonResponse
    {
        if (! $publisher->isEligible($job)) {
            return response()->json([
                'message' => sprintf(
                    'This job is below the minimum match score to publish (%d%%).',
                    config('jobhunter.min_match_to_publish', 75),
                ),
            ], 422);
        }

        $publisher->publish($job);

        return response()->json($job->fresh());
    }

    public function update(Request $request, Job $job): JsonResponse
    {
        $validated = $request->validate([
            'application_status' => ['required', new Enum(ApplicationStatus::class)],
        ]);

        $job->update($validated);

        return response()->json($job);
    }
}
