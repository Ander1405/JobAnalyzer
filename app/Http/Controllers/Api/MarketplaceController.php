<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\JobStatus;
use App\Enums\TrackedJobStatus;
use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeJobListing;
use App\Models\Job;
use App\Models\TrackedJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketplaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Offers the profile pre-filter rejected are kept in the table only so the
        // next fetch doesn't re-create and re-analyze them; they are not results.
        $query = Job::query()->with('trackedJob')->where('status', '!=', JobStatus::Discarded);

        if ($source = $request->query('source')) {
            $query->where('source', $source);
        }

        if ($workMode = $request->query('work_mode')) {
            $query->where('work_mode', $workMode);
        }

        if ($seniority = $request->query('seniority')) {
            $query->where('seniority', $seniority);
        }

        if ($language = $request->query('language')) {
            $query->where('ai_analysis->idioma', $language);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('has_salary_only')) {
            $query->where(function ($inner) {
                $inner->whereNotNull('salary_raw')
                    ->orWhereNotNull('ai_analysis->salario_normalizado');
            });
        }

        if ($request->boolean('hide_tracked')) {
            $query->doesntHave('trackedJob');
        }

        if ($request->filled('min_match')) {
            // An unanalyzed offer has no score, so it cannot clear the threshold.
            // Letting it through was why listings that don't match the profile at
            // all showed up as "Sin analizar" while the filter sat at 75%.
            // The CAST matters: json_extract yields text, and SQLite sorts every
            // text value above every number, so the comparison would be nonsense.
            $query->whereRaw(
                Job::matchScoreCastSql().' >= ?',
                [(int) $request->query('min_match')],
            );
        }

        match ($request->query('sort', 'match')) {
            'recent' => $query->orderByDesc('created_at'),
            'salary' => $query->orderByDesc('salary_raw'),
            default => $query->orderByRaw(Job::matchScoreCastSql().' DESC'),
        };

        $perPage = max(1, min(500, (int) $request->query('per_page', 20)));
        $jobs = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => $jobs->items(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'last_page' => $jobs->lastPage(),
                'pending_analysis' => $this->pendingAnalysisQuery()->count(),
            ],
        ]);
    }

    /**
     * A failed analysis is pending too: it never got a score, and the run that
     * failed it was usually a provider outage worth retrying.
     *
     * @return Builder<Job>
     */
    private function pendingAnalysisQuery(): Builder
    {
        return Job::query()->whereIn('status', [JobStatus::Fetched, JobStatus::Failed]);
    }

    public function track(Job $job): JsonResponse
    {
        $trackedJob = TrackedJob::query()->firstOrCreate(
            ['job_id' => $job->id],
            ['status' => TrackedJobStatus::SinAplicar],
        );

        return response()->json($trackedJob);
    }

    public function trackBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_ids' => ['required', 'array', 'min:1'],
            'job_ids.*' => [
                'integer',
                Rule::exists('job_offers', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        $trackedJobs = [];

        foreach ($validated['job_ids'] as $jobId) {
            $trackedJobs[] = TrackedJob::query()->firstOrCreate(
                ['job_id' => $jobId, 'user_id' => $request->user()->id],
                ['status' => TrackedJobStatus::SinAplicar],
            );
        }

        return response()->json(['tracked' => $trackedJobs]);
    }

    public function analyzePending(): JsonResponse
    {
        $pending = $this->pendingAnalysisQuery()->get();

        $pending->each(function (Job $job) {
            $job->update(['status' => JobStatus::Analyzing, 'error_message' => null]);

            AnalyzeJobListing::dispatch($job);
        });

        return response()->json(['dispatched' => $pending->pluck('id')]);
    }
}
