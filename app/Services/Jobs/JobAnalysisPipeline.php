<?php

declare(strict_types=1);

namespace App\Services\Jobs;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Services\AI\JobAnalyzer;
use App\Services\Profile\ProfileMatcher;

/**
 * Single entry point for "take a fetched offer to a scored one", shared by the
 * console command and the queued job so both apply the same policy — before
 * this existed, anything analyzed from the UI skipped the profile pre-filter
 * entirely and landed in the marketplace unscored.
 */
class JobAnalysisPipeline
{
    private ?ProfileMatcher $matcher = null;

    public function __construct(private readonly JobAnalyzer $analyzer) {}

    /**
     * Low-scoring offers are kept, not deleted: the score is what the
     * marketplace filter reads, and a deleted offer is simply re-fetched and
     * re-analyzed on the next run, paying for the same AI call again.
     *
     * $skipPreFilter is for the "analyze this one" button — when the user picks a
     * specific listing, a keyword heuristic has no business overruling them.
     */
    public function process(Job $job, bool $skipPreFilter = false): void
    {
        if (! $skipPreFilter && ! $this->matcher()->isRelevant($job)) {
            $job->update([
                'status' => JobStatus::Discarded,
                'error_message' => 'Descartada por el prefiltro de perfil: no menciona ninguna de tus tecnologías principales.',
            ]);

            return;
        }

        $this->analyzer->analyze($job);
    }

    private function matcher(): ProfileMatcher
    {
        return $this->matcher ??= ProfileMatcher::create();
    }
}
