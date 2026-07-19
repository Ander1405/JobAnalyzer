<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeJobListing;
use App\Models\Job;
use App\Services\Notion\NotionPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rules\Enum;

class JobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Job::query();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($source = $request->query('source')) {
            $query->where('source', $source);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        $jobs = $query->get();

        if ($request->filled('min_match')) {
            $minMatch = (float) $request->query('min_match');
            $jobs = $jobs->filter(fn (Job $job) => $this->matchScore($job) >= $minMatch)->values();
        }

        $jobs = $jobs->sortByDesc(fn (Job $job) => $this->matchScore($job))->values();

        $total = $jobs->count();
        $perPage = max(1, min(500, (int) $request->query('per_page', 20)));
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) $request->query('page', 1)), $lastPage);

        return response()->json([
            'data' => $jobs->forPage($page, $perPage)->values(),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }

    public function show(Job $job): JsonResponse
    {
        return response()->json($job);
    }

    public function sources(): JsonResponse
    {
        return response()->json(
            Job::query()->distinct()->orderBy('source')->pluck('source'),
        );
    }

    public function fetch(): JsonResponse
    {
        Artisan::call('jobs:fetch');

        return response()->json(['output' => Artisan::output()]);
    }

    public function analyze(Job $job): JsonResponse
    {
        $job->update(['status' => JobStatus::Analyzing, 'error_message' => null]);

        AnalyzeJobListing::dispatch($job);

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

    private function matchScore(Job $job): float
    {
        return (float) ($job->ai_analysis['match_score'] ?? -1);
    }
}
