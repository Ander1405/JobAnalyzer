<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\JobStatus;
use App\Enums\TrackedJobStatus;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\TrackedJob;
use Illuminate\Http\JsonResponse;

class NavController extends Controller
{
    public function badges(): JsonResponse
    {
        return response()->json([
            // Matches the Marketplace's default min_match filter (config
            // jobhunter.min_match_to_publish) so the number here never
            // disagrees with what the list shows on first load.
            'marketplace' => Job::query()
                ->where('status', JobStatus::Analyzed)
                ->doesntHave('trackedJob')
                ->whereRaw(
                    Job::matchScoreCastSql().' >= ?',
                    [(int) config('jobhunter.min_match_to_publish', 65)],
                )
                ->count(),
            'tracking' => TrackedJob::query()
                ->where('status', TrackedJobStatus::EnProceso)
                ->count(),
        ]);
    }
}
