<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\AI\JobAnalyzer;
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

        return response()->json($jobs);
    }

    public function show(Job $job): JsonResponse
    {
        return response()->json($job);
    }

    public function fetch(): JsonResponse
    {
        Artisan::call('jobs:fetch');

        return response()->json(['output' => Artisan::output()]);
    }

    public function analyze(Job $job, JobAnalyzer $analyzer): JsonResponse
    {
        $analyzer->analyze($job);

        return response()->json($job->fresh());
    }

    public function publish(Job $job, NotionPublisher $publisher): JsonResponse
    {
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
